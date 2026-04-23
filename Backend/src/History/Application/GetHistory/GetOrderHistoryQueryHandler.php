<?php

declare(strict_types=1);

namespace Tickets\History\Application\GetHistory;

use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\History\Response\GetOrderHistoryResponse;

class GetOrderHistoryQueryHandler implements QueryHandler
{
    public function __construct(
        private HistoryRepositoryInterface $historyRepository,
    ) {
    }

    public function __invoke(GetOrderHistoryQuery $query): GetOrderHistoryResponse
    {
        return new GetOrderHistoryResponse($this->historyRepository->getByAggregateId($query->aggregateId));
    }
}
