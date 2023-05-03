<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Scan\ElTicket;

use Baza\Shared\Domain\Bus\Query\QueryHandler;
use Baza\Tickets\Repositories\ElTicketsRepositoryInterface;
use Baza\Tickets\Responses\ElTicketResponse;

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
