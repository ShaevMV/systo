<?php

declare(strict_types=1);

namespace Baza\Sync\Applications\Export;

use Baza\Sync\Repositories\SyncRepositoryInterface;
use Carbon\Carbon;
use RuntimeException;

/**
 * Выгружает таблицы Baza в NDJSON (JSON Lines) — по одной записи на строку.
 *
 * Формат NDJSON выбран как наименее затратный: можно стримить построчно,
 * без загрузки всего массива в память — критично при росте объёмов.
 */
class ExportApplication
{
    private const CHUNK_SIZE = 1000;

    public function __construct(
        private readonly SyncRepositoryInterface $repository,
    ) {
    }

    /**
     * @return array<string, int> Статистика: ['table' => количество выгруженных строк]
     */
    public function export(string $exportDir, ?Carbon $since = null): array
    {
        $this->ensureDirectory($exportDir);

        $stats = [];

        foreach ($this->repository->getSyncTables() as $table) {
            $stats[$table] = $this->exportTable($table, $exportDir, $since);
        }

        return $stats;
    }

    private function exportTable(string $table, string $exportDir, ?Carbon $since): int
    {
        $filePath = $exportDir . DIRECTORY_SEPARATOR . $table . '.jsonl';

        $handle = @fopen($filePath, 'w');
        if ($handle === false) {
            throw new RuntimeException("Не удалось открыть файл '{$filePath}' на запись");
        }

        $count = 0;

        try {
            $this->repository->chunkForExport(
                $table,
                $since,
                self::CHUNK_SIZE,
                function (array $row) use ($handle, &$count): void {
                    $json = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                    fwrite($handle, $json . PHP_EOL);
                    $count++;
                }
            );
        } finally {
            fclose($handle);
        }

        return $count;
    }

    private function ensureDirectory(string $exportDir): void
    {
        if (is_dir($exportDir)) {
            return;
        }

        if (!mkdir($exportDir, 0775, true) && !is_dir($exportDir)) {
            throw new RuntimeException("Не удалось создать директорию '{$exportDir}'");
        }
    }
}
