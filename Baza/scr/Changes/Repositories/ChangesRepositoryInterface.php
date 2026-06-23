<?php

declare(strict_types=1);

namespace Baza\Changes\Repositories;

use Carbon\Carbon;

interface ChangesRepositoryInterface
{
    /**
     * Вывести полный отчёт
     *
     * @return array
     */
    public function getAllReport(string $festivalId): array;

    /**
     * Закрыть смену
     *
     * @param int $changeId
     * @return int
     */
    public function close(int $changeId): int;

    /**
     * Увеличить кол-во пропущенных билетов
     *
     * @param string $columName
     * @param int $changeId
     * @return bool
     */
    public function addTicket(string $columName, int $changeId): bool;

    /**
     * Получить идентификатор текущей смены пользователя
     *
     * @param int $userId
     * @return int|null
     */
    public function getChangeId(int $userId): ?int;

    public function updateOrCreate(array $userList, Carbon $start, string $festivalId, ?int $id = null, ?int $chiefId = null): bool;

    /**
     * user_id начальника смены (change_user.role = shift_chief) или null —
     * для предзаполнения формы на редактировании.
     */
    public function getChiefId(int $changeId): ?int;

    /**
     * festival_id смены (TD-48) — источник «активного фестиваля» для изоляции
     * скан/поиск/впуск по фестивалю смены. null, если смены нет или поле пусто.
     */
    public function festivalIdForChange(int $changeId): ?string;

    public function get(int $id): array;

    public function remove(int $id): bool;

    /** Существует ли смена с таким id (для 404 вместо 500 на закрытии несуществующей). */
    public function exists(int $id): bool;

    /**
     * Открытые смены (end IS NULL) для экрана управления (Шаг 6).
     * $chiefId != null → только смены, где этот пользователь — начальник (изоляция начальника).
     * $festivalId != null → только смены этого фестиваля (TD-48); null = все фестивали.
     *
     * @return array<int, array{id:int, chief_id:?int, chief_name:?string, members_count:int, start:?string, festival_id:?string, festival_name:?string, counts:array<string,int>}>
     */
    public function listOpen(?int $chiefId = null, ?string $festivalId = null): array;
}
