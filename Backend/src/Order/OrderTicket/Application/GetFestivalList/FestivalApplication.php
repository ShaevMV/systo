<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetFestivalList;

use Shared\Domain\Bus\Command\CommandBus;
use Shared\Domain\Bus\Query\QueryBus;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\Festival\Domain\Festival;
use Tickets\History\Domain\ActorType;
use Tickets\History\Domain\HistoryEventInterface;
use Tickets\History\Dto\DomainHistoryDto;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;
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
        private readonly HistoryRepositoryInterface $historyRepository,
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
     * Пишет историю создания (domain_history, aggregate_type='festival').
     *
     * @throws \Throwable
     */
    public function create(FestivalDto $data, ?string $actorId = null): bool
    {
        $this->commandBus->dispatch(new CreateFestivalCommand($data));

        $festival = Festival::created($data->getId(), $data->getName(), $data->getYear(), $data->isActive());
        $this->saveHistory($data->getId()->value(), $festival->pullHistoryEvents(), $actorId);

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
     * Редактировать фестиваль (admin). Пишет в историю список изменившихся полей.
     *
     * @throws \Throwable
     */
    public function edit(Uuid $id, FestivalDto $data, ?string $actorId = null): bool
    {
        // Снимок ДО — чтобы записать в историю именно изменившиеся поля.
        $before = $this->getItem($id)?->toArray() ?? [];

        $this->commandBus->dispatch(new FestivalEditCommand($id, $data));

        $after = $data->toArray();
        $changed = [];
        foreach (['name', 'year', 'active'] as $field) {
            if (($before[$field] ?? null) !== ($after[$field] ?? null)) {
                $changed[] = $field;
            }
        }

        $festival = Festival::existing($id);
        $festival->edited($changed); // пусто → история не пишется
        $this->saveHistory($id->value(), $festival->pullHistoryEvents(), $actorId);

        return true;
    }

    /**
     * Удалить фестиваль (admin, soft delete). Пишет историю удаления.
     *
     * @throws \Throwable
     */
    public function delete(Uuid $id, ?string $actorId = null): bool
    {
        $this->commandBus->dispatch(new FestivalDeleteCommand($id));

        $festival = Festival::existing($id);
        $festival->deleted();
        $this->saveHistory($id->value(), $festival->pullHistoryEvents(), $actorId);

        return true;
    }

    /**
     * История изменений фестиваля из domain_history (по возрастанию occurred_at).
     *
     * @return DomainHistoryDto[]
     */
    public function getHistory(Uuid $id): array
    {
        return $this->historyRepository->getByAggregateId($id->value());
    }

    /**
     * Сохранить накопленные события истории. Actor — админ (Auth::id()), тип USER.
     *
     * @param array<int, HistoryEventInterface> $events
     */
    private function saveHistory(string $aggregateId, array $events, ?string $actorId): void
    {
        foreach ($events as $event) {
            $this->historyRepository->save(new SaveHistoryDto($aggregateId, $event, $actorId, ActorType::USER));
        }
    }
}
