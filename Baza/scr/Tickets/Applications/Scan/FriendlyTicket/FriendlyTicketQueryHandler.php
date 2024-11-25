<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Scan\FriendlyTicket;

use Baza\Shared\Domain\Bus\Query\QueryHandler;
use Baza\Tickets\Repositories\FriendlyTicketRepositoryInterface;
use Baza\Tickets\Responses\FriendlyTicketResponse;

class FriendlyTicketQueryHandler implements QueryHandler
{
    public function __construct(
        private FriendlyTicketRepositoryInterface $friendlyTicketRepository,
    )
    {
    }

    public function __invoke(FriendlyTicketQuery $query): ?FriendlyTicketResponse
    {
        return $this->friendlyTicketRepository->search($query->getKilter());
    }
}
