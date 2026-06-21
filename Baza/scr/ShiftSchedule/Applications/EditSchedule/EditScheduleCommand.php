<?php

declare(strict_types=1);

namespace Baza\ShiftSchedule\Applications\EditSchedule;

use Baza\Shared\Domain\Bus\Command\Command;
use Baza\ShiftSchedule\Dto\ShiftScheduleDto;

class EditScheduleCommand implements Command
{
    public function __construct(
        private int $id,
        private ShiftScheduleDto $dto,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDto(): ShiftScheduleDto
    {
        return $this->dto;
    }
}
