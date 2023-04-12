<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\PushTicket\Get;

use Tickets\Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Ticket\CreateTickets\Repositories\PushTicketsRepositoryInterface;

class PushTicketQueryHandler implements QueryHandler
{
    public function __construct(
        private PushTicketsRepositoryInterface $ticketsRepository
    )
    {
    }

    public function __invoke(PushTicketQuery $query): PushTicketsResponse
    {
        if (is_null($query->getId())) {
            return $this->ticketsRepository->getAllTickets();
        }
        $result =   $this->ticketsRepository->getTicket($query->getId());

        return $result;
    }
}
