<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetOrderList\ForAdmin;

use App\Models\Ordering\OrderTicketModel;
use App\Models\User;
use Tickets\Order\OrderTicket\Responses\ListResponse;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemForListResponse;
use Tickets\Order\Shared\Repositories\OrderTicketRepositoryInterface;
use Tickets\Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Shared\Domain\Criteria\FilterOperator;
use Tickets\Shared\Domain\Criteria\Filters;

class OrderListFilterQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository
    )
    {
    }

    public function __invoke(OrderFilterQuery $filterQuery): ?ListResponse
    {
        $filter = Filters::fromValues([
            // email
            [
                'field' => User::TABLE . '.email',
                'operator' => FilterOperator::LIKE,
                'value' => $filterQuery->getEmail(),
            ],
            // status
            [
                'field' => OrderTicketModel::TABLE . '.status',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getStatus(),
            ],
            // types_of_payment_id
            [
                'field' => OrderTicketModel::TABLE . '.types_of_payment_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getTypesOfPayment()?->value(),
            ],
            [
                'field' => OrderTicketModel::TABLE . '.promo_code',
                'operator' => FilterOperator::LIKE,
                'value' => $filterQuery->getPromoCode(),
            ]
        ]);
        $orderTicketItem = $this->orderTicketRepository->getList($filter);

        if (!is_null($filterQuery->getPrice())) {
            $orderTicketItem = $this->filterByPrice($filterQuery->getPrice(), $orderTicketItem);
        }

        return count($orderTicketItem) > 0 ? new ListResponse($orderTicketItem) : null;
    }


    /**
     * @param float $price
     * @param OrderTicketItemForListResponse[] $orderTicketItem
     * @return array
     */
    private function filterByPrice(float $price, array $orderTicketItem): array
    {
        $result = [];

        foreach ($orderTicketItem as $item) {
            if (($item->getPriceWithoutDiscount() / count($item->getGuests()) === $price)) {
                $result[] = $item;
            }
        }

        return $result;
    }
}
