<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Application;

use Shared\Domain\Bus\Command\CommandBus;
use Shared\Domain\Bus\Query\QueryBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\TypesOfPayment\Application\GetList\TypesOfPaymentGetListQuery;
use Tickets\TypesOfPayment\Application\GetList\TypesOfPaymentGetListQueryHandler;
use Tickets\TypesOfPayment\Response\TypesOfPaymentListResponse;

class TypesOfPaymentApplication
{
    private CommandBus $commandBus;
    private QueryBus $queryBus;

    public function __construct(
        TypesOfPaymentGetListQueryHandler $typesOfPaymentGetListQueryHandler
    )
    {
        $this->queryBus = new InMemorySymfonyQueryBus([
            TypesOfPaymentGetListQuery::class => $typesOfPaymentGetListQueryHandler
        ]);
    }

    public function getList(TypesOfPaymentGetListQuery $query): TypesOfPaymentListResponse
    {
        /** @var TypesOfPaymentListResponse $result */
        $result = $this->queryBus->ask($query);

        return $result;
    }
}
