<?php

declare(strict_types=1);

namespace Baza\Sync\Applications\Import;

use Baza\Sync\Repositories\SyncRepositoryInterface;
use Carbon\Carbon;
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
            }
        } finally {
            fclose($handle);
        }

        return ['inserted' => $inserted, 'updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * Запись из файла свежее, чем в БД, по метке updated_at.
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

        return Carbon::parse($incomingAt)->gt(Carbon::parse($existingAt));
    }
}
