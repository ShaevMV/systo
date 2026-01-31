<?php

namespace Baza\Tickets\Applications\Enter\ParkingTicket;

use Baza\Shared\Domain\Bus\Command\CommandHandler;
use Baza\Tickets\Repositories\ParkingTicketRepositoryInterface;

class ParkingTicketCommandHandler implements CommandHandler
{
    public function __construct(
        private ParkingTicketRepositoryInterface $repository
    )
    {
    }

    public function __invoke(ParkingTicketCommand $command): void
    {
        $this->repository->skip($command->getId(), $command->getChangeId());
    }
}
