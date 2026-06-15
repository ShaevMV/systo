<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Tickets\Template\Domain\TemplateKind;
use Tickets\Template\Dto\TemplateDto;
use Tickets\Template\Repositories\TemplateRepositoryInterface;

/**
 * Загрузить конвертированные в Mustache шаблоны из resources/views/mustache/ в БД и АКТИВИРОВАТЬ.
 *
 * Фаза 5 (конвертация blade→Mustache) идёт по одному файлу: добавил {slug}.{kind}.mustache —
 * прогнал команду — шаблон активен, реальные письма/билеты рендерятся из БД. Публикация пишет
 * версию (template_versions), так что откат к blade — деактивация записи в админке.
 *
 * Запускать вручную (НЕ в авто-деплое): активация меняет реальный рендер, делаем осознанно.
 */
class SyncConvertedTemplatesCommand extends Command
{
    protected $signature = 'templates:sync-converted';

    protected $description = 'Загрузить конвертированные Mustache-шаблоны (resources/views/mustache/) в БД и активировать';

    public function handle(TemplateRepositoryInterface $repository): int
    {
        $dir = resource_path('views/mustache');

        if (! File::isDirectory($dir)) {
            $this->warn('Каталог не найден: ' . $dir);

            return self::SUCCESS;
        }

        $synced = 0;
        foreach (File::files($dir) as $file) {
            $name = $file->getFilename();
            if (! str_ends_with($name, '.mustache')) {
                continue;
            }

            // Формат имени: {slug}.{kind}.mustache  (slug'и проекта без точек).
            $base = substr($name, 0, -strlen('.mustache'));
            $dot = strrpos($base, '.');
            if ($dot === false) {
                $this->warn("  ! пропуск {$name}: ожидается формат slug.kind.mustache");
                continue;
            }
            $kind = substr($base, $dot + 1);
            $slug = substr($base, 0, $dot);

            if (! TemplateKind::isValid($kind)) {
                $this->warn("  ! пропуск {$name}: kind='{$kind}' невалиден (email|pdf)");
                continue;
            }

            $body = File::get($file->getPathname());
            $existing = $repository->findBySlugKind($slug, $kind);

            if ($existing !== null) {
                $repository->publish($existing->getId(), $body, null, 'Конвертация blade→Mustache (sync)');
                $repository->activate($existing->getId(), true);
                $this->line("  ✓ обновлён и активирован {$kind}:{$slug}");
            } else {
                $repository->create(TemplateDto::fromState([
                    'slug' => $slug,
                    'kind' => $kind,
                    'engine' => 'html',
                    'title' => $slug,
                    'body' => $body,
                    'active' => true,
                    'is_system' => true,
                ]));
                $this->line("  ✓ создан и активирован {$kind}:{$slug}");
            }
            $synced++;
        }

        $this->info("Синхронизировано шаблонов: {$synced}.");

        return self::SUCCESS;
    }
}
