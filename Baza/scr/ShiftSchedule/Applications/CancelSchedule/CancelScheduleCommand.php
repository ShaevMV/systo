<?php

declare(strict_types=1);

namespace Baza\ShiftSchedule\Applications\CancelSchedule;

use Baza\Shared\Domain\Bus\Command\Command;

class CancelScheduleCommand implements Command
{
    public function __construct(
        private int $id,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
