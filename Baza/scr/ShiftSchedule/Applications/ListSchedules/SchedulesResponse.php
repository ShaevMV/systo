<?php

declare(strict_types=1);

namespace Baza\ShiftSchedule\Applications\ListSchedules;

use Baza\Shared\Domain\Bus\Query\Response;

class SchedulesResponse implements Response
{
    /**
     * @param  array<int, array<string, mixed>>  $schedules
     */
    public function __construct(
        private array $schedules,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSchedules(): array
    {
        return $this->schedules;
    }
}
