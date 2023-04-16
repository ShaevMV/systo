<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\PushTicket\PushUpdate;

use DomainException;
use Tickets\Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Ticket\CreateTickets\Dto\PushTicketsDto;
use Tickets\Ticket\CreateTickets\Repositories\PushTicketsRepositoryInterface;

class PushTicketsCommandHandler implements CommandHandler
{
    public function __construct(
        private PushTicketsRepositoryInterface $ticketsRepository
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
