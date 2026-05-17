<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Scan\ElTicket;

use Baza\Shared\Domain\Bus\Query\QueryHandler;
use Baza\Tickets\Repositories\ElTicketsRepositoryInterface;
use Baza\Tickets\Repositories\SpisokTicketsRepositoryInterface;
use Baza\Tickets\Responses\ElTicketResponse;
use Baza\Tickets\Responses\SpisokTicketResponse;

class ElTicketsQueryHandler implements QueryHandler
{
    public function __construct(
        private ElTicketsRepositoryInterface     $elTicketsRepository,
        private SpisokTicketsRepositoryInterface $spisokTicketsRepository
    )
    {
    }

    public function __invoke(ElTicketQuery $query): null|ElTicketResponse|SpisokTicketResponse
    {
        if ($result = $this->elTicketsRepository->search($query->getUuid())) {
            return $result;
        }

        return $this->spisokTicketsRepository->search($query->getUuid());
    }
}
