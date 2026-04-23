<?php

declare(strict_types=1);

namespace Tickets\History\Application\GetHistory;

use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\History\Dto\DomainHistoryDto;
use Tickets\History\Response\GetOrderHistoryResponse;

class GetOrderHistory
{
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(GetOrderHistoryQueryHandler $handler)
    {
        $this->queryBus = new InMemorySymfonyQueryBus([
            GetOrderHistoryQuery::class => $handler,
        ]);
    }

    /** @return DomainHistoryDto[] */
    public function getByOrderId(string $aggregateId): array
    {
        /** @var GetOrderHistoryResponse $result */
        if ($result = $this->queryBus->ask(new GetOrderHistoryQuery($aggregateId))) {
            return $result->list;
        }

        return [];
    }
}
