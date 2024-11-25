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
    public function getAllReport(): array;

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

    public function updateOrCreate(array $userList, Carbon $start, ?int $id = null): bool;

    public function get(int $id): array;

    public function remove(int $id): bool;
}
