<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Search\LiveTicket;

use Baza\Shared\Domain\Bus\Query\QueryHandler;
use Baza\Tickets\Repositories\LiveTicketRepositoryInterface;
use Baza\Tickets\Repositories\SpisokTicketsRepositoryInterface;

class LiveTicketQueryHandler implements QueryHandler
{
    public function __construct(
        private LiveTicketRepositoryInterface $liveTicketRepository,
    )
    {
    }

    public function __invoke(LiveTicketQuery $query): ?LiveTicketResponse
    {
        return $this->liveTicketRepository->search($query->getKilter());
    }
}
