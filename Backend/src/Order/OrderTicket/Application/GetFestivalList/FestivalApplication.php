<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetFestivalList;



use Shared\Domain\Bus\Command\CommandBus;
use Shared\Domain\Bus\Query\QueryBus;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\Order\OrderTicket\Application\CreateFestival\CreateFestivalCommand;
use Tickets\Order\OrderTicket\Application\CreateFestival\CreateFestivalCommandHandler;
use Tickets\Order\OrderTicket\Dto\Festival\FestivalDto;
use Tickets\Order\OrderTicket\Responses\FestivalListResponse;

class FestivalApplication
{
    private QueryBus $queryBus;
    private CommandBus $commandBus;

    public function __construct(
        private GetFestivalListQueryHandler $festivalListQueryHandler,
        CreateFestivalCommandHandler $createFestivalCommandHandler,
    )
    {
        $this->queryBus = new InMemorySymfonyQueryBus([
            GetFestivalListQuery::class => $this->festivalListQueryHandler,
        ]);

        $this->commandBus = new InMemorySymfonyCommandBus([
            CreateFestivalCommand::class => $createFestivalCommandHandler,
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
}
