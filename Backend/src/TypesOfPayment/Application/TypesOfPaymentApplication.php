<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Application;

use Shared\Domain\Bus\Command\CommandBus;
use Shared\Domain\Bus\Query\QueryBus;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\TypesOfPayment\Application\Create\TypesOfPaymentCreateCommand;
use Tickets\TypesOfPayment\Application\Create\TypesOfPaymentCreateCommandHandler;
use Tickets\TypesOfPayment\Application\Delete\TypesOfPaymentDeleteCommand;
use Tickets\TypesOfPayment\Application\Delete\TypesOfPaymentDeleteCommandHandler;
use Tickets\TypesOfPayment\Application\Edit\TypesOfPaymentEditCommand;
use Tickets\TypesOfPayment\Application\Edit\TypesOfPaymentEditCommandHandler;
use Tickets\TypesOfPayment\Application\GetList\TypesOfPaymentGetListQuery;
use Tickets\TypesOfPayment\Application\GetList\TypesOfPaymentGetListQueryHandler;
use Tickets\TypesOfPayment\Application\GetItem\TypesOfPaymentGetItemQuery;
use Tickets\TypesOfPayment\Application\GetItem\TypesOfPaymentGetItemQueryHandler;
use Tickets\TypesOfPayment\Dto\TypesOfPaymentDto;
use Tickets\TypesOfPayment\Response\TypesOfPaymentListResponse;

class TypesOfPaymentApplication
{
    private CommandBus $commandBus;
    private QueryBus $queryBus;

    public function __construct(
        TypesOfPaymentGetListQueryHandler $typesOfPaymentGetListQueryHandler,
        TypesOfPaymentGetItemQueryHandler $typesOfPaymentGetItemQueryHandler,

        TypesOfPaymentEditCommandHandler  $typesOfPaymentEditCommandHandler,
        TypesOfPaymentCreateCommandHandler  $typesOfPaymentCreateCommandHandler,
        TypesOfPaymentDeleteCommandHandler  $typesOfPaymentDeleteCommandHandler,
    )
    {
        $this->queryBus = new InMemorySymfonyQueryBus([
            TypesOfPaymentGetListQuery::class => $typesOfPaymentGetListQueryHandler,
            TypesOfPaymentGetItemQuery::class => $typesOfPaymentGetItemQueryHandler,
        ]);

        $this->commandBus = new InMemorySymfonyCommandBus([
            TypesOfPaymentEditCommand::class => $typesOfPaymentEditCommandHandler,
            TypesOfPaymentCreateCommand::class => $typesOfPaymentCreateCommandHandler,
            TypesOfPaymentDeleteCommand::class => $typesOfPaymentDeleteCommandHandler,
        ]);
    }

    public function getList(TypesOfPaymentGetListQuery $query): TypesOfPaymentListResponse
    {
        /** @var TypesOfPaymentListResponse $result */
        $result = $this->queryBus->ask($query);

        return $result;
    }

    public function getItem(Uuid $uuid): TypesOfPaymentDto
    {
        /** @var TypesOfPaymentDto $result */
        $result = $this->queryBus->ask(new TypesOfPaymentGetItemQuery($uuid));

        return $result;
    }

    /**
     * @throws \Throwable
     */
    public function edit(Uuid $id, TypesOfPaymentDto $paymentDto): bool
    {
        $this->commandBus->dispatch(new TypesOfPaymentEditCommand($id, $paymentDto));
        return true;
    }

    /**
     * @throws \Throwable
     */
    public function create(TypesOfPaymentDto $paymentDto): bool
    {
        $this->commandBus->dispatch(new TypesOfPaymentCreateCommand($paymentDto));

        return true;
    }

    /**
     * @throws \Throwable
     */
    public function delete(Uuid $id): bool
    {
        $this->commandBus->dispatch(new TypesOfPaymentDeleteCommand($id));

        return true;
    }
}
