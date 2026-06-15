<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Tickets\Template\Domain\TemplateEngine;
use Tickets\Template\Domain\TemplateKind;
use Tickets\Template\Dto\TemplateDto;
use Tickets\Template\Repositories\TemplateRepositoryInterface;

/**
 * Импорт текущих blade-шаблонов (письма + PDF-билеты) в таблицу templates как НЕАКТИВНЫЕ
 * системные черновики (active=0, is_system=1). Слой данных для последующей ручной конвертации
 * в Mustache (фаза 5). slug = имени blade-файла → совпадает с getFestivalView()/emailView.
 *
 * Идемпотентно: существующие (slug, kind) пропускаются (не затираем правки админа).
 * Активацию делает админ из UI после конвертации — до этого рендер падает на blade (fallback).
 */
class ImportBladeTemplatesCommand extends Command
{
    protected $signature = 'templates:import-blade';

    protected $description = 'Импорт blade-шаблонов (письма + PDF) в таблицу templates (неактивные системные черновики)';

    public function handle(TemplateRepositoryInterface $repository): int
    {
        $created = 0;
        $skipped = 0;

        // Письма: resources/views/email/*.blade.php
        foreach ($this->bladeFiles(resource_path('views/email')) as $slug => $path) {
            [$c, $s] = $this->import($repository, $slug, TemplateKind::EMAIL, $path);
            $created += $c;
            $skipped += $s;
        }

        // PDF-билеты: resources/views/*.blade.php (верхний уровень), кроме служебных.
        foreach ($this->bladeFiles(resource_path('views')) as $slug => $path) {
            if (in_array($slug, ['welcome'], true)) {
                continue;
            }
            [$c, $s] = $this->import($repository, $slug, TemplateKind::PDF, $path);
            $created += $c;
            $skipped += $s;
        }

        $this->info("Импорт завершён: создано {$created}, пропущено {$skipped} (уже существуют).");

        return self::SUCCESS;
    }

    /**
     * @return array<string, string> slug => полный путь
     */
    private function bladeFiles(string $dir): array
    {
        if (! File::isDirectory($dir)) {
            return [];
        }

        $result = [];
        foreach (File::files($dir) as $file) {
            $name = $file->getFilename();
            if (! str_ends_with($name, '.blade.php')) {
                continue;
            }
            $slug = substr($name, 0, -strlen('.blade.php'));
            $result[$slug] = $file->getPathname();
        }

        return $result;
    }

    /**
     * @return array{0: int, 1: int} [created, skipped]
     */
    private function import(TemplateRepositoryInterface $repository, string $slug, string $kind, string $path): array
    {
        if ($repository->findBySlugKind($slug, $kind) !== null) {
            $this->line("  • skip {$kind}:{$slug} (уже есть)");

            return [0, 1];
        }

        $repository->create(TemplateDto::fromState([
            'slug' => $slug,
            'kind' => $kind,
            'engine' => TemplateEngine::HTML,
            'title' => $slug,
            'body' => File::get($path),
            'active' => false,     // raw blade ≠ Mustache → не активируем; рендер падает на blade
            'is_system' => true,
        ]));

        $this->line("  ✓ создан {$kind}:{$slug}");

        return [1, 0];
    }
}
