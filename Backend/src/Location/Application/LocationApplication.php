<?php

declare(strict_types=1);

namespace Tickets\Location\Application;

use Shared\Domain\Bus\Command\CommandBus;
use Shared\Domain\Bus\Query\QueryBus;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\Location\Application\Create\LocationCreateCommand;
use Tickets\Location\Application\Create\LocationCreateCommandHandler;
use Tickets\Location\Application\Delete\LocationDeleteCommand;
use Tickets\Location\Application\Delete\LocationDeleteCommandHandler;
use Tickets\Location\Application\Edit\LocationEditCommand;
use Tickets\Location\Application\Edit\LocationEditCommandHandler;
use Tickets\Location\Application\GetItem\LocationGetItemQuery;
use Tickets\Location\Application\GetItem\LocationGetItemQueryHandler;
use Tickets\Location\Application\GetList\LocationGetListQuery;
use Tickets\Location\Application\GetList\LocationGetListQueryHandler;
use Tickets\Location\Dto\LocationDto;
use Tickets\Location\Response\LocationGetListResponse;

class LocationApplication
{
    private CommandBus $commandBus;
    private QueryBus $queryBus;

    public function __construct(
        LocationGetListQueryHandler $locationGetListQueryHandler,
        LocationGetItemQueryHandler $locationGetItemQueryHandler,
        LocationCreateCommandHandler $locationCreateCommandHandler,
        LocationEditCommandHandler $locationEditCommandHandler,
        LocationDeleteCommandHandler $locationDeleteCommandHandler,
    ) {
        $this->commandBus = new InMemorySymfonyCommandBus([
            LocationCreateCommand::class => $locationCreateCommandHandler,
            LocationEditCommand::class => $locationEditCommandHandler,
            LocationDeleteCommand::class => $locationDeleteCommandHandler,
        ]);

        $this->queryBus = new InMemorySymfonyQueryBus([
            LocationGetListQuery::class => $locationGetListQueryHandler,
            LocationGetItemQuery::class => $locationGetItemQueryHandler,
        ]);
    }

    public function getList(LocationGetListQuery $query): LocationGetListResponse
    {
        /** @var LocationGetListResponse $result */
        $result = $this->queryBus->ask($query);

        return $result;
    }

    public function getItem(Uuid $uuid): LocationDto
    {
        /** @var LocationDto $result */
        $result = $this->queryBus->ask(new LocationGetItemQuery($uuid));

        return $result;
    }

    /**
     * @throws \Throwable
     */
    public function edit(Uuid $id, LocationDto $data): bool
    {
        $this->commandBus->dispatch(new LocationEditCommand($id, $data));

        return true;
    }

    /**
     * @throws \Throwable
     */
    public function create(LocationDto $data): bool
    {
        $this->commandBus->dispatch(new LocationCreateCommand($data));

        return true;
    }

    /**
     * @throws \Throwable
     */
    public function delete(Uuid $id): bool
    {
        $this->commandBus->dispatch(new LocationDeleteCommand($id));

        return true;
    }
}
