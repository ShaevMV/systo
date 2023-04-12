<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\PushTicket;

use DomainException;
use Nette\Utils\JsonException;
use Throwable;
use Tickets\Shared\Domain\Bus\Command\CommandBus;
use Tickets\Shared\Domain\Bus\Query\QueryBus;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Tickets\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\Ticket\CreateTickets\Application\PushTicket\Get\PushTicketQuery;
use Tickets\Ticket\CreateTickets\Application\PushTicket\Get\PushTicketQueryHandler;
use Tickets\Ticket\CreateTickets\Application\PushTicket\Get\PushTicketsResponse;
use Tickets\Ticket\CreateTickets\Application\PushTicket\Set\SetPushTicketCommand;
use Tickets\Ticket\CreateTickets\Application\PushTicket\Set\SetPushTicketCommandHandler;

class PushTicket
{
    private QueryBus $bus;
    private CommandBus $commandBus;

    public function __construct(
        private PushTicketQueryHandler      $pushTicketQueryHandler,
        private SetPushTicketCommandHandler $setPushTicketCommandHandler,
    )
    {
        $this->bus = new InMemorySymfonyQueryBus([
            PushTicketQuery::class => $this->pushTicketQueryHandler
        ]);

        $this->commandBus = new InMemorySymfonyCommandBus([
            SetPushTicketCommand::class => $setPushTicketCommandHandler
        ]);
    }


    /**
     * @throws JsonException
     * @throws DomainException
     * @throws Throwable
     */
    public function pushTicket(?Uuid $id = null): array
    {
        $resultList = [];
        /** @var PushTicketsResponse $pushTicketsResponse */
        $pushTicketsResponse = $this->bus->ask(new PushTicketQuery($id));
        foreach ($pushTicketsResponse->getTicketDto() as $ticketsDto) {
            $this->commandBus->dispatch(new SetPushTicketCommand($ticketsDto));

            $resultList[] = $ticketsDto->getUuid()->value();
        }

        return $resultList;
    }
}
