<?php

declare(strict_types=1);

namespace Baza\Sync\Repositories;

use Carbon\Carbon;

/**
 * Репозиторий синхронизации таблиц Baza с другим инстансом через NDJSON файлы.
 *
 * Принцип чистой архитектуры (Роберт Мартин — «Чистая архитектура»):
 * только этот репозиторий знает об Eloquent-моделях. Application/Command-слой
 * получает уже массивы данных и не делает прямых обращений к БД.
 */
interface SyncRepositoryInterface
{
    /**
     * Список таблиц, разрешённых для синхронизации.
     *
     * @return string[]
     */
    public function getSyncTables(): array;

    /**
     * Стримит записи таблицы порциями для экспорта (через Eloquent chunk —
     * чтобы не держать всё в памяти). При $since != null отдаются только
     * записи с updated_at > $since (инкрементальная выгрузка).
     *
     * @param callable(array): void $callback Вызывается на каждую строку
     */
    public function chunkForExport(string $table, ?Carbon $since, int $chunkSize, callable $callback): void;

    /**
     * Получить запись таблицы по первичному ключу (id).
     *
     * @return array<string, mixed>|null
     */
    public function findById(string $table, int $id): ?array;

    /**
     * Bulk-выборка для импорта: один SELECT вместо N findById().
     * Возвращает мапу [id => updated_at] для существующих записей.
     * Отсутствующие id в результат не попадают — значит запись надо INSERT'ить.
     *
     * @param int[] $ids
     * @return array<int, string|null>
     */
    public function findUpdatedAtByIds(string $table, array $ids): array;

    /**
     * Вставить сырую запись (без срабатывания Eloquent events/casts —
     * чтобы сохранить timestamps как есть в источнике).
     *
     * @param array<string, mixed> $data
     */
    public function insert(string $table, array $data): bool;

    /**
     * Обновить запись по id (id из массива удаляется перед UPDATE).
     *
     * @param array<string, mixed> $data
     */
    public function update(string $table, int $id, array $data): bool;
}
