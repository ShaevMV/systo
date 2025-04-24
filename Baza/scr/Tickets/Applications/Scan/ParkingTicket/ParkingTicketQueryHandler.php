<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Scan\ParkingTicket;

use Baza\Shared\Domain\Bus\Query\QueryHandler;
use Baza\Tickets\Repositories\ParkingTicketRepositoryInterface;
use Baza\Tickets\Responses\ParkingTicketResponse;

class ParkingTicketQueryHandler implements QueryHandler
{
    public function __construct(
        private ParkingTicketRepositoryInterface $parkingTicketRepository,
    )
    {
    }

    public function __invoke(ParkingTicketQuery $query): ?ParkingTicketResponse
    {
        return $this->parkingTicketRepository->search(
            $query->getKilter(),
            $query->getType(),
        );
    }
}
