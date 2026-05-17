<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Scan\LiveTicket;

use Baza\Shared\Domain\Bus\Query\QueryHandler;
use Baza\Tickets\Repositories\LiveTicketRepositoryInterface;
use Baza\Tickets\Responses\LiveTicketResponse;
use Baza\Tickets\Live\Service\TicketLiveService;

class LiveTicketQueryHandler implements QueryHandler
{
    public function __construct(
        private LiveTicketRepositoryInterface $liveTicketRepository,
    )
    {
    }

    public function __invoke(LiveTicketQuery $query): ?LiveTicketResponse
    {
        $number = TicketLiveService::decrypt($query->getKilter());

        return $this->liveTicketRepository->search((int)$number);
    }
}
