<?php

namespace Tickets\Ticket\CreateTickets\Application\PushTicket\Set;

use Tickets\Shared\Domain\Bus\Command\Command;
use Tickets\Ticket\CreateTickets\Dto\PushTicketsDto;

class SetPushTicketCommand implements Command
{
    public function __construct(
        private PushTicketsDto $ticketsDto
    )
    {
    }

    public function getTicketsDto(): PushTicketsDto
    {
        return $this->ticketsDto;
    }
}
