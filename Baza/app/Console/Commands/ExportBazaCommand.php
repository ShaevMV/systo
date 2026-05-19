<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Baza\Sync\Applications\Export\ExportApplication;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class ExportBazaCommand extends Command
{
    protected $signature = 'baza:export
        {--since= : Выгружать только записи с updated_at > этой даты (ISO 8601)}
        {--path= : Путь к директории выгрузки (по умолчанию storage/app/baza-export)}';

    protected $description = 'Выгрузка таблиц Baza (auto, changes, el_tickets, live_tickets, parking_tickets, spisok_tickets) в NDJSON';

    public function handle(ExportApplication $exportApplication): int
    {
        $exportDir = (string)($this->option('path') ?: storage_path('app/baza-export'));
        $sinceOpt = $this->option('since');

        try {
            $since = $sinceOpt !== null ? Carbon::parse($sinceOpt) : null;
        } catch (Throwable $e) {
            $this->error('Невалидная дата --since: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info(sprintf(
            'Выгрузка → %s%s',
            $exportDir,
            $since !== null ? " (с {$since->toDateTimeString()})" : ''
        ));

        try {
            $stats = $exportApplication->export($exportDir, $since);
        } catch (Throwable $e) {
            $this->error('Ошибка выгрузки: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $rows = [];
        $total = 0;
        foreach ($stats as $table => $count) {
            $rows[] = [$table, $count];
            $total += $count;
        }

        $this->table(['Таблица', 'Выгружено'], $rows);
        $this->info("Итого: {$total} строк");

        return Command::SUCCESS;
    }
}
