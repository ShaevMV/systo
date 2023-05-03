<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Scan\LiveTicket;

use Baza\Shared\Domain\Bus\Query\QueryHandler;
use Baza\Tickets\Repositories\LiveTicketRepositoryInterface;
use Baza\Tickets\Responses\LiveTicketResponse;

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
