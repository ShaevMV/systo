<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\AddTicketsInReport;

use Baza\Shared\Domain\Bus\Command\Command;

class AddTicketsInReportCommand implements Command
{
    public function __construct(
        private int $changeId,
        private string $typeTicket,
    )
    {
    }

    public function getChangeId(): int
    {
        return $this->changeId;
    }

    public function getTypeTicket(): string
    {
        return $this->typeTicket;
    }
}
