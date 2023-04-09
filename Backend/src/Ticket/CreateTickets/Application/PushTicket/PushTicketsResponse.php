<?php

namespace Tickets\Ticket\CreateTickets\Application\PushTicket;

use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Ticket\CreateTickets\Dto\TicketDto;

class PushTicketsResponse implements Response
{
    /**
     * @param TicketDto[] $ticketDto
     */
    public function __construct(
        private array $ticketDto,
    )
    {
    }

    public function getTicketDto(): array
    {
        return $this->ticketDto;
    }
}
