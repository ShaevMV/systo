<?php

namespace App\Console\Commands;

use App\Models\ReportConfig;
use App\Models\ReportRunLog;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Tickets\Reports\Domain\ReportHandlerRegistry;
use Tickets\Reports\Infrastructure\GoogleSheetsClient;

class ExportReportsToGoogle extends Command
{
    protected $signature = 'reports:export-to-google';
    protected $description = 'Экспорт активных отчётов в Google Sheets';

    public function handle(
        ReportHandlerRegistry $registry,
        GoogleSheetsClient $googleClient
    ): int {
        $configs = ReportConfig::where('is_active', true)->get();

        if ($configs->isEmpty()) {
            $this->warn('Нет активных конфигураций для экспорта');
            return Command::SUCCESS;
        }

        $this->info("Найдено активных отчётов: {$configs->count()}");

        foreach ($configs as $config) {
            $handler = $registry->get($config->report_type);

            if (!$handler) {
                $this->error("[{$config->name}] Обработчик для типа '{$config->report_type}' не найден");
                continue;
            }

            $log = $this->createLog($config);

            try {
                $this->info("[{$config->name}] Начало экспорта...");

                $filters = $config->filters ?? [];
                $data = $handler->getData($filters);

                $rows = [$handler->getHeaders()];
                foreach ($data as $index => $row) {
                    $rows[] = $handler->formatRow($row, $index);
                }

                $range = "{$config->sheet_name}!A{$config->start_row}";
                $googleClient->appendRows(
                    $config->spreadsheet_id,
                    $range,
                    $rows
                );

                $rowCount = count($rows) - 1;

                $config->updateLastRun('success', $rowCount, "Выгружено {$rowCount} строк");
                $this->updateLog($log, 'success', $rowCount);

                $this->info("[{$config->name}] ✓ Успешно выгружено {$rowCount} строк");
                $this->info("URL: https://docs.google.com/spreadsheets/d/{$config->spreadsheet_id}");

            } catch (\Throwable $e) {
                $config->updateLastRun('failed', 0, $e->getMessage());
                $this->updateLog($log, 'failed', 0, $e->getMessage());

                $this->error("[{$config->name}] ✗ Ошибка: {$e->getMessage()}");
            }
        }

        return Command::SUCCESS;
    }

    private function createLog(ReportConfig $config): ReportRunLog
    {
        return ReportRunLog::create([
            'id' => Str::uuid(),
            'report_config_id' => $config->id,
            'started_at' => now(),
            'status' => 'running',
            'exported_rows' => 0,
        ]);
    }

    private function updateLog(ReportRunLog $log, string $status, int $rows, ?string $error = null): void
    {
        $log->update([
            'finished_at' => now(),
            'status' => $status,
            'exported_rows' => $rows,
            'error_message' => $error,
        ]);
    }
}
