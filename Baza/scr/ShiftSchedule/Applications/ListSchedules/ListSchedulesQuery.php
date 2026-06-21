<?php

declare(strict_types=1);

namespace Baza\ShiftSchedule\Applications\ListSchedules;

use Baza\Shared\Domain\Bus\Query\Query;

class ListSchedulesQuery implements Query
{
    public function __construct(
        private string $festivalId,
        private ?string $shiftDate = null,
        private ?string $kppPoint = null,
    ) {
    }

    public function getFestivalId(): string
    {
        return $this->festivalId;
    }

    public function getShiftDate(): ?string
    {
        return $this->shiftDate;
    }

    public function getKppPoint(): ?string
    {
        return $this->kppPoint;
    }
}
