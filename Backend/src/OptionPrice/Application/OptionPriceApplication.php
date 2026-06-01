<?php

declare(strict_types=1);

namespace Tickets\OptionPrice\Application;

use Shared\Domain\Bus\Command\CommandBus;
use Shared\Domain\Bus\Query\QueryBus;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\OptionPrice\Application\Create\OptionPriceCreateCommand;
use Tickets\OptionPrice\Application\Create\OptionPriceCreateCommandHandler;
use Tickets\OptionPrice\Application\Delete\OptionPriceDeleteCommand;
use Tickets\OptionPrice\Application\Delete\OptionPriceDeleteCommandHandler;
use Tickets\OptionPrice\Application\Edit\OptionPriceEditCommand;
use Tickets\OptionPrice\Application\Edit\OptionPriceEditCommandHandler;
use Tickets\OptionPrice\Application\GetItem\OptionPriceGetItemQuery;
use Tickets\OptionPrice\Application\GetItem\OptionPriceGetItemQueryHandler;
use Tickets\OptionPrice\Application\GetList\OptionPriceGetListQuery;
use Tickets\OptionPrice\Application\GetList\OptionPriceGetListQueryHandler;
use Tickets\OptionPrice\Dto\OptionPriceDto;
use Tickets\OptionPrice\Response\OptionPriceGetListResponse;

/**
 * Application-фасад модуля волн цен опций (v2.6.0).
 *
 * Полный аналог `TicketTypePriceApplication`. Управляет таблицей
 * `option_price` (волны цен по аналогии с `ticket_type_price`).
 *
 * См. `.claude/specs/ticket-options.md`.
 */
class OptionPriceApplication
{
    private CommandBus $commandBus;

    private QueryBus $queryBus;

    public function __construct(
        OptionPriceGetListQueryHandler $getListQueryHandler,
        OptionPriceGetItemQueryHandler $getItemQueryHandler,
        OptionPriceCreateCommandHandler $createCommandHandler,
        OptionPriceEditCommandHandler $editCommandHandler,
        OptionPriceDeleteCommandHandler $deleteCommandHandler,
    ) {
        $this->commandBus = new InMemorySymfonyCommandBus([
            OptionPriceCreateCommand::class => $createCommandHandler,
            OptionPriceEditCommand::class => $editCommandHandler,
            OptionPriceDeleteCommand::class => $deleteCommandHandler,
        ]);

        $this->queryBus = new InMemorySymfonyQueryBus([
            OptionPriceGetListQuery::class => $getListQueryHandler,
            OptionPriceGetItemQuery::class => $getItemQueryHandler,
        ]);
    }

    public function getList(OptionPriceGetListQuery $query): OptionPriceGetListResponse
    {
        /** @var OptionPriceGetListResponse $result */
        $result = $this->queryBus->ask($query);

        return $result;
    }

    public function getItem(Uuid $uuid): OptionPriceDto
    {
        /** @var OptionPriceDto $result */
        $result = $this->queryBus->ask(new OptionPriceGetItemQuery($uuid));

        return $result;
    }

    /**
     * @throws \Throwable
     */
    public function create(OptionPriceDto $data): bool
    {
        $this->commandBus->dispatch(new OptionPriceCreateCommand($data));

        return true;
    }

    /**
     * @throws \Throwable
     */
    public function edit(Uuid $id, OptionPriceDto $data): bool
    {
        $this->commandBus->dispatch(new OptionPriceEditCommand($id, $data));

        return true;
    }

    /**
     * @throws \Throwable
     */
    public function delete(Uuid $id): bool
    {
        $this->commandBus->dispatch(new OptionPriceDeleteCommand($id));

        return true;
    }
}
