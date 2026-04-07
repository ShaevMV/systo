<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetOrderList\ForFriendly;

use App\Models\Ordering\OrderTicketModel;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\Order\OrderTicket\Responses\ListResponse;

class OrderListFilterQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository
    ) {
    }

    public function __invoke(OrderFilterQuery $filterQuery): ?ListResponse
    {
        $filter = Filters::fromValues($this->getFilterValues($filterQuery));

        $orderTicketItem = $this->orderTicketRepository->getFriendlyList($filter);

        $result = [];

        foreach ($orderTicketItem as $value) {
            $result[] = $value->setGuests($value->getGuestsByFestivalId($filterQuery->getFestivalId()));
        }

        return count($result) > 0 ? new ListResponse($result) : null;
    }

    private function getFilterValues(OrderFilterQuery $filterQuery): array
    {
        $result = [

            [
                'field' => OrderTicketModel::TABLE.'.guests',
                'operator' => FilterOperator::LIKE,
                'value' => $filterQuery->getEmail(),
            ],

            [
                'field' => OrderTicketModel::TABLE.'.guests',
                'operator' => FilterOperator::LIKE,
                'value' => $filterQuery->getName(),
            ],
            [
                'field' => OrderTicketModel::TABLE.'.festival_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getFestivalId()?->value(),
            ],
            [
                'field' => OrderTicketModel::TABLE.'.friendly_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getFriendlyId()?->value(),
            ],
            [
                'field' => OrderTicketModel::TABLE.'.status',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getStatus(),
            ],
        ];

        if ($filterQuery->getUserId() !== null) {
            $result[] = [
                'field' => OrderTicketModel::TABLE.'.friendly_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getUserId()?->value(),
            ];
        }

        return $result;
    }
}
