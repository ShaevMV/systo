<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Search\ElTicket;

use Baza\Shared\Domain\Bus\Query\QueryHandler;
use Baza\Tickets\Repositories\ElTicketsRepositoryInterface;

class ElTicketsQueryHandler implements QueryHandler
{
    public function __construct(
        private ElTicketsRepositoryInterface $elTicketsRepository
    )
    {
    }

    public function __invoke(ElTicketQuery $query): ?ElTicketResponse
    {
        return $this->elTicketsRepository->search($query->getUuid());
    }
}
