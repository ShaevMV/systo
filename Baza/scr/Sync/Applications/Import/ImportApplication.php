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
     * Размер чанка: компромисс между числом SELECT-ов и размером IN-списка.
     * 500 даёт ~80 запросов на 40k строк вместо 40k.
     */
    private const CHUNK_SIZE = 500;

    /**
     * @return array{inserted:int, updated:int, skipped:int}
     */
    private function importTable(string $table, string $filePath): array
    {
        $handle = @fopen($filePath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Не удалось открыть файл '{$filePath}' на чтение");
        }

        $stats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0];
        /** @var array<int, array> $buffer id => row */
        $buffer = [];

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                try {
                    $row = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                } catch (Throwable) {
                    $stats['skipped']++;
                    continue;
                }

                if (!is_array($row) || !isset($row['id'])) {
                    $stats['skipped']++;
                    continue;
                }

                $buffer[(int)$row['id']] = $row;

                if (count($buffer) >= self::CHUNK_SIZE) {
                    $this->processChunk($table, $buffer, $stats);
                    $buffer = [];
                }
            }

            // Хвост — если последний чанк не дотянул до CHUNK_SIZE
            if (!empty($buffer)) {
                $this->processChunk($table, $buffer, $stats);
            }
        } finally {
            fclose($handle);
        }

        return $stats;
    }

    /**
     * Обработка чанка: один SELECT для всех id, затем per-id insert/update.
     * Без транзакции — лояльный контракт: одна сбойная строка → skip, остальные доезжают.
     *
     * @param array<int, array> $buffer
     * @param array{inserted:int, updated:int, skipped:int} &$stats
     */
    private function processChunk(string $table, array $buffer, array &$stats): void
    {
        $existing = $this->repository->findUpdatedAtByIds($table, array_keys($buffer));

        foreach ($buffer as $id => $row) {
            try {
                if (!array_key_exists($id, $existing)) {
                    $this->repository->insert($table, $row);
                    $stats['inserted']++;
                    continue;
                }

                if ($this->isFresherThan($row['updated_at'] ?? null, $existing[$id])) {
                    $this->repository->update($table, $id, $row);
                    $stats['updated']++;
                } else {
                    $stats['skipped']++;
                }
            } catch (Throwable $e) {
                $stats['skipped']++;
                Log::warning('Sync import: строка пропущена', [
                    'table' => $table,
                    'id'    => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param string|null $incomingAt из JSONL (ISO-8601 или Y-m-d H:i:s)
     * @param string|null $existingAt из БД (Y-m-d H:i:s)
     */
    private function isFresherThan(?string $incomingAt, ?string $existingAt): bool
    {
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
