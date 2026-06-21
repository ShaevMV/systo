<?php

declare(strict_types=1);

namespace Baza\ShiftSchedule\Repositories;

use Baza\ShiftSchedule\Dto\ShiftScheduleDto;

/**
 * Плановое расписание смен (PR-A). БД — ТОЛЬКО в реализации (Dependency Rule).
 */
interface ShiftScheduleRepositoryInterface
{
    /**
     * Создать плановую смену + её состав (shift_schedule_user).
     *
     * @return int id созданной плановой смены
     */
    public function create(ShiftScheduleDto $dto): int;

    /**
     * Изменить плановую смену + пересобрать состав.
     */
    public function edit(int $id, ShiftScheduleDto $dto): bool;

    /**
     * Отменить плановую смену (status = cancelled).
     */
    public function cancel(int $id): bool;

    /**
     * Существует ли план с таким id (для 404 вместо 500).
     */
    public function exists(int $id): bool;

    /**
     * Полный план по id (с составом) или null.
     */
    public function find(int $id): ?array;

    /**
     * Список планов фестиваля для сетки (проекция). Опц. фильтры — дата и точка КПП.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForFestival(string $festivalId, ?string $shiftDate = null, ?string $kppPoint = null): array;

    /**
     * Личное расписание сотрудника: открытые/будущие смены (shift_date >= сегодня,
     * status != cancelled), где он состоит в составе.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMySchedule(int $userId): array;
}
