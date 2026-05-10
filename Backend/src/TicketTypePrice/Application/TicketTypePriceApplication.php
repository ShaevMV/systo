<?php

declare(strict_types=1);

namespace Tickets\TicketTypePrice\Application;

use Shared\Domain\Bus\Command\CommandBus;
use Shared\Domain\Bus\Query\QueryBus;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\TicketTypePrice\Application\Create\TicketTypePriceCreateCommand;
use Tickets\TicketTypePrice\Application\Create\TicketTypePriceCreateCommandHandler;
use Tickets\TicketTypePrice\Application\Delete\TicketTypePriceDeleteCommand;
use Tickets\TicketTypePrice\Application\Delete\TicketTypePriceDeleteCommandHandler;
use Tickets\TicketTypePrice\Application\Edit\TicketTypePriceEditCommand;
use Tickets\TicketTypePrice\Application\Edit\TicketTypePriceEditCommandHandler;
use Tickets\TicketTypePrice\Application\GetItem\TicketTypePriceGetItemQuery;
use Tickets\TicketTypePrice\Application\GetItem\TicketTypePriceGetItemQueryHandler;
use Tickets\TicketTypePrice\Application\GetList\TicketTypePriceGetListQuery;
use Tickets\TicketTypePrice\Application\GetList\TicketTypePriceGetListQueryHandler;
use Tickets\TicketTypePrice\Dto\TicketTypePriceDto;
use Tickets\TicketTypePrice\Response\TicketTypePriceGetListResponse;

class TicketTypePriceApplication
{
    private CommandBus $commandBus;

    private QueryBus $queryBus;

    public function __construct(
        TicketTypePriceGetListQueryHandler $getListQueryHandler,
        TicketTypePriceGetItemQueryHandler $getItemQueryHandler,
        TicketTypePriceCreateCommandHandler $createCommandHandler,
        TicketTypePriceEditCommandHandler $editCommandHandler,
        TicketTypePriceDeleteCommandHandler $deleteCommandHandler,
    ) {
        $this->commandBus = new InMemorySymfonyCommandBus([
            TicketTypePriceCreateCommand::class => $createCommandHandler,
            TicketTypePriceEditCommand::class => $editCommandHandler,
            TicketTypePriceDeleteCommand::class => $deleteCommandHandler,
        ]);

        $this->queryBus = new InMemorySymfonyQueryBus([
            TicketTypePriceGetListQuery::class => $getListQueryHandler,
            TicketTypePriceGetItemQuery::class => $getItemQueryHandler,
        ]);
    }

    public function getList(TicketTypePriceGetListQuery $query): TicketTypePriceGetListResponse
    {
        /** @var TicketTypePriceGetListResponse $result */
        $result = $this->queryBus->ask($query);

        return $result;
    }

    public function getItem(Uuid $uuid): TicketTypePriceDto
    {
        /** @var TicketTypePriceDto $result */
        $result = $this->queryBus->ask(new TicketTypePriceGetItemQuery($uuid));

        return $result;
    }

    /**
     * @throws \Throwable
     */
    public function create(TicketTypePriceDto $data): bool
    {
        $this->commandBus->dispatch(new TicketTypePriceCreateCommand($data));

        return true;
    }

    /**
     * @throws \Throwable
     */
    public function edit(Uuid $id, TicketTypePriceDto $data): bool
    {
        $this->commandBus->dispatch(new TicketTypePriceEditCommand($id, $data));

        return true;
    }

    /**
     * @throws \Throwable
     */
    public function delete(Uuid $id): bool
    {
        $this->commandBus->dispatch(new TicketTypePriceDeleteCommand($id));

        return true;
    }
}
