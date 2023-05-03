<?php

namespace Baza\Tickets\Applications\Enter\LiveTicket;

use Baza\Shared\Domain\Bus\Command\CommandHandler;
use Baza\Tickets\Repositories\ElTicketsRepositoryInterface;
use Baza\Tickets\Repositories\LiveTicketRepositoryInterface;
use Baza\Tickets\Repositories\SpisokTicketsRepositoryInterface;

class LiveTicketCommandHandler implements CommandHandler
{
    public function __construct(
        private LiveTicketRepositoryInterface $repository
    )
    {
    }

    public function __invoke(LiveTicketCommand $command): void
    {
        $this->repository->skip($command->getId(), $command->getUserId());
    }
}
