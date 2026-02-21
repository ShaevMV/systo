<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Application;

use Shared\Domain\Bus\Command\CommandBus;
use Shared\Domain\Bus\Query\QueryBus;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
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
        TypesOfPaymentGetItemQueryHandler $typesOfPaymentGetItemQueryHandler
    )
    {
        $this->queryBus = new InMemorySymfonyQueryBus([
            TypesOfPaymentGetListQuery::class => $typesOfPaymentGetListQueryHandler,
            TypesOfPaymentGetItemQuery::class => $typesOfPaymentGetItemQueryHandler,
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
}
