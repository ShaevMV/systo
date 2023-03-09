<?php

declare(strict_types =1);

namespace Tickets\Ticket\CreateTickets\Application\CreateForFriendly;

use Tickets\Shared\Domain\Bus\Command\Command;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Domain\Ticket;
use Tickets\Ticket\CreateTickets\Dto\TicketDto;

class CreateTicketFriendlyCommand implements Command
{
    public function __construct(
        private TicketDto $ticketDto
    ){
    }

    /**
     * @return TicketDto
     */
    public function getTicketDto(): TicketDto
    {
        return $this->ticketDto;
    }
}
