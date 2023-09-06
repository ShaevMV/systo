<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Enter\SpisokTicket;

use Baza\Shared\Domain\Bus\Command\CommandHandler;
use Baza\Tickets\Repositories\AutoTicketRepositoryInterface;

class AutoTicketCommandHandler implements CommandHandler
{
    public function __construct(
        private AutoTicketRepositoryInterface $repository
    )
    {
    }

    public function __invoke(SpisokTicketCommand $command): void
    {
        $this->repository->skip($command->getId(), $command->getChangeId());
    }
}
