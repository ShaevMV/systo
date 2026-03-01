<?php

namespace Tickets\Ticket\CreateTickets\Application\PushTicketLive;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;

class PushTicketsLiveCommand implements Command
{
    public function __construct(
        private Uuid $id,
        private int  $number,
    )
    {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getNumber(): int
    {
        return $this->number;
    }
}
