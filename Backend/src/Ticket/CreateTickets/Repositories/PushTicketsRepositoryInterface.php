<?php

namespace Tickets\Ticket\CreateTickets\Repositories;

use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Application\PushTicket\PushTicketsResponse;
use Tickets\Ticket\CreateTickets\Dto\TicketDto;

interface PushTicketsRepositoryInterface
{
    public function getTicket(Uuid $ticketId): PushTicketsResponse;

    public function getAllTickets(): PushTicketsResponse;
}
