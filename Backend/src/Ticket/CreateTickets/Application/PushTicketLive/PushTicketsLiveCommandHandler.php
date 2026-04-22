<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\PushTicketLive;

use DomainException;
use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

class PushTicketsLiveCommandHandler implements CommandHandler
{
    public function __construct(
        private TicketsRepositoryInterface $ticketsRepository
    )
    {
    }

    public function __invoke(PushTicketsLiveCommand $command): void
    {
        if (!$this->ticketsRepository->setInBazaLive(
            $command->getNumber(),
            $command->getId(),
        )) {
            throw new DomainException('При записи произошла ошибка '. $command->getId()->value());
        };
    }
}
