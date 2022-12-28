<?php

namespace Tickets\Ticket\CreateTickets\Repositories;

use Tickets\Ticket\CreateTickets\Dto\TicketDto;

interface TicketsRepositoryInterface
{
    public function createTickets(TicketDto $ticketDto): bool;
}
