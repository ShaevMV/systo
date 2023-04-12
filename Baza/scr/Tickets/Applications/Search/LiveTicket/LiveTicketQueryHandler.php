<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Search\LiveTicket;

use Baza\Shared\Domain\Bus\Query\QueryHandler;
use Baza\Tickets\Repositories\SpisokTicketsRepositoryInterface;

class LiveTicketQueryHandler implements QueryHandler
{
    public function __construct(
        private SpisokTicketsRepositoryInterface $spisokTicketsRepository,
    )
    {
    }

    public function __invoke(LiveTicketQuery $query): ?LiveTicketResponse
    {
        return $this->spisokTicketsRepository->search($query->getKilter());
    }
}
