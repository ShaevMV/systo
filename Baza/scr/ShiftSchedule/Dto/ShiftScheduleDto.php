<?php

declare(strict_types=1);

namespace Baza\ShiftSchedule\Dto;

use Carbon\Carbon;

/**
 * Плановое расписание смены (PR-A) — план «кто/когда/где должен работать».
 *
 * Пассивный DTO (по образцу EntryOutboxDto). $members — состав плана с ролями:
 * массив элементов ['userId' => int, 'role' => string].
 */
final class ShiftScheduleDto
{
    /**
     * @param  array<int, array{userId: int, role: string}>  $members
     */
    public function __construct(
        public readonly ?int $id,
        public readonly string $festivalId,
        public readonly ?string $kppPoint,
        public readonly Carbon $shiftDate,
        public readonly Carbon $plannedStart,
        public readonly ?Carbon $plannedEnd,
        public readonly ?string $name,
        public readonly string $status,
        public readonly ?int $chiefId,
        public readonly array $members,
    ) {}
}
