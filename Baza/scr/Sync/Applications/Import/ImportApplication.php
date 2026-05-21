<?php

declare(strict_types=1);

namespace Baza\Sync\Applications\Import;

use Baza\Sync\Repositories\SyncRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Загружает таблицы Baza из NDJSON-файлов с инкрементальным upsert.
 *
 * Логика:
 *  - запись с таким id не существует → INSERT;
 *  - существует, но updated_at в файле новее → UPDATE;
 *  - иначе SKIP.
 *
 * Удалённые на источнике записи не трогаются (по требованию).
 */
class ImportApplication
{
    public function __construct(
        private readonly SyncRepositoryInterface $repository,
    ) {
    }

    /**
     * @return array<string, array{inserted:int, updated:int, skipped:int, missing?:bool}>
     */
    public function import(string $importDir): array
    {
        if (!is_dir($importDir)) {
            throw new RuntimeException("Директория импорта '{$importDir}' не существует");
        }

        $stats = [];

        foreach ($this->repository->getSyncTables() as $table) {
            $filePath = $importDir . DIRECTORY_SEPARATOR . $table . '.jsonl';

            if (!is_file($filePath)) {
                $stats[$table] = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'missing' => true];
                continue;
            }

            $stats[$table] = $this->importTable($table, $filePath);
        }

        return $stats;
    }

    /**
     * @return array{inserted:int, updated:int, skipped:int}
     */
    private function importTable(string $table, string $filePath): array
    {
        $handle = @fopen($filePath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Не удалось открыть файл '{$filePath}' на чтение");
        }

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                try {
                    $row = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                } catch (Throwable) {
                    $skipped++;
                    continue;
                }

                if (!is_array($row) || !isset($row['id'])) {
                    $skipped++;
                    continue;
                }

                $id = (int)$row['id'];

                // Per-row try/catch: невалидные timestamp, ошибка БД, ошибка normalize() и т.п.
                // → пропускаем строку, не валим весь импорт. Контекст (таблица + id) уходит в лог.
                try {
                    $existing = $this->repository->findById($table, $id);

                    if ($existing === null) {
                        $this->repository->insert($table, $row);
                        $inserted++;
                        continue;
                    }

                    if ($this->isFresher($row, $existing)) {
                        $this->repository->update($table, $id, $row);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } catch (Throwable $e) {
                    $skipped++;
                    Log::warning('Sync import: строка пропущена', [
                        'table'   => $table,
                        'id'      => $id,
                        'error'   => $e->getMessage(),
                    ]);
                }
            }
        } finally {
            fclose($handle);
        }

        return ['inserted' => $inserted, 'updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * Запись из файла свежее, чем в БД, по метке updated_at.
     *
     * При невалидном формате updated_at бросаем RuntimeException — caller поймает
     * в общем per-row try/catch и пропустит строку с логом.
     */
    private function isFresher(array $incoming, array $existing): bool
    {
        $incomingAt = $incoming['updated_at'] ?? null;
        $existingAt = $existing['updated_at'] ?? null;

        if ($incomingAt === null) {
            return false;
        }

        if ($existingAt === null) {
            return true;
        }

        try {
            return Carbon::parse($incomingAt)->gt(Carbon::parse($existingAt));
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Не удалось сравнить updated_at: incoming=' . var_export($incomingAt, true)
                    . ', existing=' . var_export($existingAt, true),
                0,
                $e,
            );
        }
    }
}
