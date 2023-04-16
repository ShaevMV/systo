<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\PushTicket;

use DomainException;
use Tickets\Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

class PushTicketsCommandHandler implements CommandHandler
{
    public function __construct(
        private TicketsRepositoryInterface $ticketsRepository
    )
    {
    }

    public function __invoke(PushTicketsCommand $command): void
    {
        $pushTicketsDto = $this->ticketsRepository->getTicket($command->getId());

        foreach ($pushTicketsDto as $item) {
            if (!$this->ticketsRepository->setInBaza($item)) {
                throw new DomainException('При записи произошла ошибка');
            };
        }
    }
}
