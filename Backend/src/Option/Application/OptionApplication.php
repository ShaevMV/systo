<?php

declare(strict_types=1);

namespace Tickets\Option\Application;

use Shared\Domain\Bus\Command\CommandBus;
use Shared\Domain\Bus\Query\QueryBus;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\Option\Application\Create\OptionCreateCommand;
use Tickets\Option\Application\Create\OptionCreateCommandHandler;
use Tickets\Option\Application\Delete\OptionDeleteCommand;
use Tickets\Option\Application\Delete\OptionDeleteCommandHandler;
use Tickets\Option\Application\Edit\OptionEditCommand;
use Tickets\Option\Application\Edit\OptionEditCommandHandler;
use Tickets\Option\Application\GetItem\OptionGetItemQuery;
use Tickets\Option\Application\GetItem\OptionGetItemQueryHandler;
use Tickets\Option\Application\GetList\OptionGetListQuery;
use Tickets\Option\Application\GetList\OptionGetListQueryHandler;
use Tickets\Option\Dto\OptionDto;
use Tickets\Option\Dto\OptionForTicketTypeView;
use Tickets\Option\Dto\OptionTicketTypeBindingDto;
use Tickets\Option\Repositories\OptionRepositoryInterface;
use Tickets\Option\Response\OptionGetListResponse;

/**
 * Application-фасад модуля Опций к билетам (v2.6.0).
 *
 * Тонкая обёртка над CommandBus + QueryBus, агрегирующая 5 базовых
 * CRUD-операций. Используется из контроллера (`OptionController`).
 *
 * Также экспонирует:
 *  - `getTicketTypeBindings()` — список привязок к типам билетов (для админки);
 *  - `getActiveOptionsForTicketType()` — read-модель для фронта (форма
 *    покупки билета), уже с подмешанной актуальной ценой и описанием.
 *
 * См. `.claude/specs/ticket-options.md`.
 */
class OptionApplication
{
    private CommandBus $commandBus;

    private QueryBus $queryBus;

    public function __construct(
        OptionGetListQueryHandler $optionGetListQueryHandler,
        OptionGetItemQueryHandler $optionGetItemQueryHandler,
        OptionCreateCommandHandler $optionCreateCommandHandler,
        OptionEditCommandHandler $optionEditCommandHandler,
        OptionDeleteCommandHandler $optionDeleteCommandHandler,
        private OptionRepositoryInterface $repository,
    ) {
        $this->commandBus = new InMemorySymfonyCommandBus([
            OptionCreateCommand::class => $optionCreateCommandHandler,
            OptionEditCommand::class => $optionEditCommandHandler,
            OptionDeleteCommand::class => $optionDeleteCommandHandler,
        ]);

        $this->queryBus = new InMemorySymfonyQueryBus([
            OptionGetListQuery::class => $optionGetListQueryHandler,
            OptionGetItemQuery::class => $optionGetItemQueryHandler,
        ]);
    }

    public function getList(OptionGetListQuery $query): OptionGetListResponse
    {
        /** @var OptionGetListResponse $result */
        $result = $this->queryBus->ask($query);

        return $result;
    }

    public function getItem(Uuid $uuid): OptionDto
    {
        /** @var OptionDto $result */
        $result = $this->queryBus->ask(new OptionGetItemQuery($uuid));

        return $result;
    }

    /**
     * @param  OptionTicketTypeBindingDto[]  $bindings
     *
     * @throws \Throwable
     */
    public function create(OptionDto $data, array $bindings = []): bool
    {
        $this->commandBus->dispatch(new OptionCreateCommand($data, $bindings));

        return true;
    }

    /**
     * @param  OptionTicketTypeBindingDto[]|null  $bindings  null — не трогаем привязки
     *
     * @throws \Throwable
     */
    public function edit(Uuid $id, OptionDto $data, ?array $bindings = null): bool
    {
        $this->commandBus->dispatch(new OptionEditCommand($id, $data, $bindings));

        return true;
    }

    /**
     * @throws \Throwable
     */
    public function delete(Uuid $id): bool
    {
        $this->commandBus->dispatch(new OptionDeleteCommand($id));

        return true;
    }

    /**
     * Получить привязки опции к типам билетов (с описаниями).
     *
     * @return OptionTicketTypeBindingDto[]
     */
    public function getTicketTypeBindings(Uuid $optionId): array
    {
        return $this->repository->getTicketTypeBindings($optionId);
    }

    /**
     * Получить активные опции для конкретного типа билета.
     * Используется фронтом при покупке (форма выбора опций).
     *
     * @return OptionForTicketTypeView[]
     */
    public function getActiveOptionsForTicketType(Uuid $ticketTypeId): array
    {
        return $this->repository->getActiveOptionsForTicketType($ticketTypeId);
    }
}
