<?php

declare(strict_types=1);

namespace Tickets\TicketType\Application;

use Shared\Domain\Bus\Command\CommandBus;
use Shared\Domain\Bus\Query\QueryBus;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\TicketType\Application\Create\TicketTypeCreateCommand;
use Tickets\TicketType\Application\Create\TicketTypeCreateCommandHandler;
use Tickets\TicketType\Application\Delete\TicketTypeDeleteCommand;
use Tickets\TicketType\Application\Delete\TicketTypeDeleteCommandHandler;
use Tickets\TicketType\Application\Edit\TicketTypeEditCommand;
use Tickets\TicketType\Application\Edit\TicketTypeEditCommandHandler;
use Tickets\TicketType\Application\GetItem\TicketTypeGetItemQuery;
use Tickets\TicketType\Application\GetItem\TicketTypeGetItemQueryHandler;
use Tickets\TicketType\Application\GetList\TicketTypeGetListQuery;
use Tickets\TicketType\Application\GetList\TicketTypeGetListQueryHandler;
use Tickets\TicketType\Dto\TicketTypeDto;
use Tickets\TicketType\Response\TicketTypeGetListResponse;

class TicketTypeApplication
{
    private CommandBus $commandBus;
    private QueryBus $queryBus;

    public function __construct(
        TicketTypeGetListQueryHandler $ticketTypeGetListQueryHandler,
        TicketTypeGetItemQueryHandler $ticketTypeGetItemQueryHandler,

        TicketTypeCreateCommandHandler $ticketTypeCreateCommandHandler,
        TicketTypeEditCommandHandler $ticketTypeEditCommandHandler,
        TicketTypeDeleteCommandHandler $ticketTypeDeleteCommandHandler,
    )
    {
        $this->commandBus = new InMemorySymfonyCommandBus([
            TicketTypeCreateCommand::class =>  $ticketTypeCreateCommandHandler,
            TicketTypeEditCommand::class =>  $ticketTypeEditCommandHandler,
            TicketTypeDeleteCommand::class =>  $ticketTypeDeleteCommandHandler,
        ]);

        $this->queryBus = new InMemorySymfonyQueryBus([
            TicketTypeGetListQuery::class => $ticketTypeGetListQueryHandler,
            TicketTypeGetItemQuery::class => $ticketTypeGetItemQueryHandler,
        ]);
    }

    public function getList(TicketTypeGetListQuery $query): TicketTypeGetListResponse
    {
        /** @var TicketTypeGetListResponse $result */
        $result = $this->queryBus->ask($query);

        return $result;
    }

    public function getItem(Uuid $uuid): TicketTypeDto
    {
        /** @var TicketTypeDto $result */
        $result = $this->queryBus->ask(new TicketTypeGetItemQuery($uuid));

        return $result;
    }

    /**
     * @throws \Throwable
     */
    public function edit(Uuid $id, TicketTypeDto $data): bool
    {
        $this->commandBus->dispatch(new TicketTypeEditCommand($id, $data));
        return true;
    }

    /**
     * @throws \Throwable
     */
    public function create(TicketTypeDto $data): bool
    {
        $this->commandBus->dispatch(new TicketTypeCreateCommand($data));

        return true;
    }

    /**
     * @throws \Throwable
     */
    public function delete(Uuid $id): bool
    {
        $this->commandBus->dispatch(new TicketTypeDeleteCommand($id));

        return true;
    }
}
