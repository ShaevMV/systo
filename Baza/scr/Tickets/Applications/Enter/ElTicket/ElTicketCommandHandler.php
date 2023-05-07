<?php

namespace Baza\Tickets\Applications\Enter\ElTicket;

use Baza\Shared\Domain\Bus\Command\CommandHandler;
use Baza\Tickets\Repositories\ElTicketsRepositoryInterface;
use Baza\Tickets\Repositories\SpisokTicketsRepositoryInterface;

class ElTicketCommandHandler implements CommandHandler
{
    public function __construct(
        private ElTicketsRepositoryInterface $repository
    )
    {
    }

    public function __invoke(ElTicketCommand $command): void
    {
        $this->repository->skip($command->getId(), $command->getChangeId());
    }
}
