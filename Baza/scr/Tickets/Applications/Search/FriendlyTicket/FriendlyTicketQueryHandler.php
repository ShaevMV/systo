<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Search\FriendlyTicket;

use Baza\Shared\Domain\Bus\Query\QueryHandler;
use Baza\Tickets\Repositories\SpisokTicketsRepositoryInterface;

class FriendlyTicketQueryHandler implements QueryHandler
{
    public function __construct(
        private SpisokTicketsRepositoryInterface $spisokTicketsRepository,
    )
    {
    }

    public function __invoke(SpisokTicketQuery $query): ?SpisokTicketResponse
    {
        return $this->spisokTicketsRepository->search($query->getKilter());
    }
}
