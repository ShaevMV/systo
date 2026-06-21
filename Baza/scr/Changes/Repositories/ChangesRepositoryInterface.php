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

    public function get(int $id): array;

    public function remove(int $id): bool;

    /**
     * Открытые смены (end IS NULL) текущего фестиваля для экрана управления (Шаг 6).
     * $chiefId != null → только смены, где этот пользователь — начальник (изоляция начальника).
     *
     * @return array<int, array{id:int, chief_id:?int, chief_name:?string, members_count:int, start:?string, counts:array<string,int>}>
     */
    public function listOpen(?int $chiefId = null): array;
}
