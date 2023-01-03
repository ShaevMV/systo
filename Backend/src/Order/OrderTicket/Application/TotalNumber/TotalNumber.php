<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\TotalNumber;

use Tickets\Order\OrderTicket\Responses\ListResponse;
use Tickets\Order\OrderTicket\Responses\TotalNumberResponse;
use Tickets\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

class TotalNumber
{
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(
        TotalNumberQueryHandler $handler
    ) {
        $this->queryBus = new InMemorySymfonyQueryBus([
            TotalNumberQuery::class => $handler
        ]);
    }

    public function getTotalNumber(ListResponse $listResponse): TotalNumberResponse
    {
        /** @var TotalNumberResponse $result */
        $result = $this->queryBus->ask(new TotalNumberQuery($listResponse));

        return $result;
    }
}
