<?php

namespace Baza\Tickets\Applications\Enter\SpisokTicket;

use Baza\Shared\Domain\Bus\Command\Command;

class SpisokTicketCommand implements Command
{
    public function __construct(
        private int $id,
        private int $changeId,
    )
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getChangeId(): int
    {
        return $this->changeId;
    }
}
