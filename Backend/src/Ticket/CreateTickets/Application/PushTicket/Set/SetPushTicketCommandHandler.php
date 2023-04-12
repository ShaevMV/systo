<?php

namespace Tickets\Ticket\CreateTickets\Application\PushTicket\Set;

use Tickets\Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Ticket\CreateTickets\Repositories\PushTicketsRepositoryInterface;

class SetPushTicketCommandHandler implements CommandHandler
{
    public function __construct(
        private PushTicketsRepositoryInterface $repository
    )
    {
    }

    public function __invoke(SetPushTicketCommand $command): void
    {
        if (!$this->repository->setInBaza($command->getTicketsDto())) {
            throw new \DomainException('При записи произошла ошибка');
        };
    }
}
