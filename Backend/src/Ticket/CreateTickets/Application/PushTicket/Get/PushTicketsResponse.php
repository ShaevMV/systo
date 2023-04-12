<?php

namespace Tickets\Ticket\CreateTickets\Application\PushTicket\Get;

use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Ticket\CreateTickets\Dto\PushTicketsDto;

class PushTicketsResponse implements Response
{
    /**
     * @param PushTicketsDto[] $ticketDto
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
