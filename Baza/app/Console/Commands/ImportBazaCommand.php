<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Baza\Sync\Applications\Import\ImportApplication;
use Illuminate\Console\Command;
use Throwable;

class ImportBazaCommand extends Command
{
    protected $signature = 'baza:import
        {--path= : Путь к директории импорта (по умолчанию storage/app/baza-export)}';

    protected $description = 'Загрузка таблиц Baza из NDJSON c upsert по updated_at (auto, changes, el_tickets, live_tickets, parking_tickets, spisok_tickets)';

    public function handle(ImportApplication $importApplication): int
    {
        $importDir = (string)($this->option('path') ?: storage_path('app/baza-export'));

        $this->info("Загрузка ← {$importDir}");

        try {
            $stats = $importApplication->import($importDir);
        } catch (Throwable $e) {
            $this->error('Ошибка загрузки: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $rows = [];
        $totalInserted = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;

        foreach ($stats as $table => $info) {
            if (!empty($info['missing'])) {
                $rows[] = [$table, '—', '—', '—', 'нет файла'];
                continue;
            }

            $rows[] = [$table, $info['inserted'], $info['updated'], $info['skipped'], ''];
            $totalInserted += $info['inserted'];
            $totalUpdated  += $info['updated'];
            $totalSkipped  += $info['skipped'];
        }

        $this->table(['Таблица', 'Создано', 'Обновлено', 'Пропущено', 'Прим.'], $rows);
        $this->info("Итого: создано {$totalInserted}, обновлено {$totalUpdated}, пропущено {$totalSkipped}");

        return Command::SUCCESS;
    }
}
