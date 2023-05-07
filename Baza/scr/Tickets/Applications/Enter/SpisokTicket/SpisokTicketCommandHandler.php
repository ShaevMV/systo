<?php

namespace Baza\Tickets\Applications\Enter\SpisokTicket;

use Baza\Shared\Domain\Bus\Command\CommandHandler;
use Baza\Tickets\Repositories\SpisokTicketsRepositoryInterface;

class SpisokTicketCommandHandler implements CommandHandler
{
    public function __construct(
        private SpisokTicketsRepositoryInterface $repository
    )
    {
    }

    public function __invoke(SpisokTicketCommand $command): void
    {
        $this->repository->skip($command->getId(), $command->getChangeId());
    }
}
