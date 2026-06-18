<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetFestivalList;

use Shared\Domain\Bus\Command\CommandBus;
use Shared\Domain\Bus\Query\QueryBus;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\Order\OrderTicket\Application\CreateFestival\CreateFestivalCommand;
use Tickets\Order\OrderTicket\Application\CreateFestival\CreateFestivalCommandHandler;
use Tickets\Order\OrderTicket\Application\Delete\FestivalDeleteCommand;
use Tickets\Order\OrderTicket\Application\Delete\FestivalDeleteCommandHandler;
use Tickets\Order\OrderTicket\Application\Edit\FestivalEditCommand;
use Tickets\Order\OrderTicket\Application\Edit\FestivalEditCommandHandler;
use Tickets\Order\OrderTicket\Application\GetItem\FestivalGetItemQuery;
use Tickets\Order\OrderTicket\Application\GetItem\FestivalGetItemQueryHandler;
use Tickets\Order\OrderTicket\Application\GetList\FestivalGetListQuery;
use Tickets\Order\OrderTicket\Application\GetList\FestivalGetListQueryHandler;
use Tickets\Order\OrderTicket\Dto\Festival\FestivalDto;
use Tickets\Order\OrderTicket\Responses\FestivalGetListResponse;
use Tickets\Order\OrderTicket\Responses\FestivalListResponse;

class FestivalApplication
{
    private QueryBus $queryBus;
    private CommandBus $commandBus;

    public function __construct(
        GetFestivalListQueryHandler $festivalListQueryHandler,
        CreateFestivalCommandHandler $createFestivalCommandHandler,
        FestivalGetListQueryHandler $festivalGetListQueryHandler,
        FestivalGetItemQueryHandler $festivalGetItemQueryHandler,
        FestivalEditCommandHandler $festivalEditCommandHandler,
        FestivalDeleteCommandHandler $festivalDeleteCommandHandler,
    ) {
        $this->queryBus = new InMemorySymfonyQueryBus([
            GetFestivalListQuery::class => $festivalListQueryHandler,
            FestivalGetListQuery::class => $festivalGetListQueryHandler,
            FestivalGetItemQuery::class => $festivalGetItemQueryHandler,
        ]);

        $this->commandBus = new InMemorySymfonyCommandBus([
            CreateFestivalCommand::class => $createFestivalCommandHandler,
            FestivalEditCommand::class => $festivalEditCommandHandler,
            FestivalDeleteCommand::class => $festivalDeleteCommandHandler,
        ]);
    }

    /**
     * Создать фестиваль (admin). БД — только в репозитории (через CommandHandler).
     *
     * @throws \Throwable
     */
    public function create(FestivalDto $data): bool
    {
        $this->commandBus->dispatch(new CreateFestivalCommand($data));

        return true;
    }

    public function getAllFestival(): FestivalListResponse
    {
        /** @var FestivalListResponse $response */
        $response = $this->queryBus->ask(
            new GetFestivalListQuery()
        );

        return $response;
    }

    /**
     * Список фестивалей с фильтрами/сортировкой (админ-CRUD).
     */
    public function getList(FestivalGetListQuery $query): FestivalGetListResponse
    {
        /** @var FestivalGetListResponse $result */
        $result = $this->queryBus->ask($query);

        return $result;
    }

    public function getItem(Uuid $id): ?FestivalDto
    {
        /** @var FestivalDto|null $result */
        $result = $this->queryBus->ask(new FestivalGetItemQuery($id));

        return $result;
    }

    /**
     * @throws \Throwable
     */
    public function edit(Uuid $id, FestivalDto $data): bool
    {
        $this->commandBus->dispatch(new FestivalEditCommand($id, $data));

        return true;
    }

    /**
     * @throws \Throwable
     */
    public function delete(Uuid $id): bool
    {
        $this->commandBus->dispatch(new FestivalDeleteCommand($id));

        return true;
    }
}
