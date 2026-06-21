<?php

declare(strict_types=1);

namespace Baza\ShiftSchedule\Applications\CreateSchedule;

use Baza\Shared\Domain\Bus\Command\Command;
use Baza\ShiftSchedule\Dto\ShiftScheduleDto;

class CreateScheduleCommand implements Command
{
    public function __construct(
        private ShiftScheduleDto $dto,
    ) {
    }

    public function getDto(): ShiftScheduleDto
    {
        return $this->dto;
    }
}
