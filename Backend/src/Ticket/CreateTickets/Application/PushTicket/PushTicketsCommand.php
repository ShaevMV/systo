<?php

namespace Tickets\Ticket\CreateTickets\Application\PushTicket;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;

class PushTicketsCommand implements Command
{
    public function __construct(
        private ?Uuid $id=null
    )
    {
    }

    /**
     * @return Uuid|null
     */
    public function getId(): ?Uuid
    {
        return $this->id;
    }
}
