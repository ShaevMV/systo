<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\GetTicket;

use Tickets\Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

class GetTicketHandler implements QueryHandler
{
    public function __construct(
        private TicketsRepositoryInterface $ticketsRepository,
    ){
    }

    public function __invoke(GetTicketQuery $query): TicketResponse
    {
        return $this->ticketsRepository->getTicket($query->getId());
    }
}
