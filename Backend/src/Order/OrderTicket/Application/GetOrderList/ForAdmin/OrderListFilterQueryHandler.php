<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetOrderList\ForAdmin;

use App\Models\Ordering\InfoForOrder\TicketTypesModel;
use App\Models\Ordering\OrderTicketModel;
use App\Models\Ordering\TicketTypeFestivalModel;
use App\Models\User;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\Order\OrderTicket\Responses\ListResponse;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemForListResponse;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;

class OrderListFilterQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository
    )
    {
    }

    public function __invoke(OrderFilterQuery $filterQuery): ?ListResponse
    {
        $filter = Filters::fromValues($this->getFilterValues($filterQuery));

        $orderTicketItem = $this->orderTicketRepository->getList($filter);

        if (!is_null($filterQuery->getPrice())) {
            $orderTicketItem = $this->filterByPrice($filterQuery->getPrice(), $orderTicketItem, $filterQuery->getFestivalId());
        }

        $result = [];

        foreach ($orderTicketItem as $value) {
            $result[] = $value->setGuests($value->getGuestsByFestivalId($filterQuery->getFestivalId()));
        }


        return count($result) > 0 ? new ListResponse($result) : null;
    }


    private function getFilterValues(OrderFilterQuery $filterQuery): array
    {
        $result = [
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
                'field' => User::TABLE . '.city',
                'operator' => FilterOperator::LIKE,
                'value' => $filterQuery->getCity(),
            ],
            [
                'field' => OrderTicketModel::TABLE . '.promo_code',
                'operator' => FilterOperator::LIKE,
                'value' => $filterQuery->getPromoCode(),
            ],
            [
                'field' => OrderTicketModel::TABLE . '.ticket_type_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getTypeOrder()?->value(),
            ],
            [
                'field' => TicketTypeFestivalModel::TABLE . '.festival_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getFestivalId()?->value(),
            ],
        ];

        if ($filterQuery->isManager()) {
            $result[] = [
                'field' => OrderTicketModel::TABLE . '.status',
                'operator' => FilterOperator::NOT_EQUAL,
                'value' => Status::NEW,
            ];
        }

        return $result;
    }


    /**
     * @param OrderTicketItemForListResponse[] $orderTicketItem
     * @return OrderTicketItemForListResponse[]
     */
    private function filterByPrice(
        float $price,
        array $orderTicketItem,
        Uuid  $festivalId,
    ): array
    {
        $result = [];

        foreach ($orderTicketItem as $item) {
            if (($item->getPriceWithoutDiscount() / count($item->getGuestsByFestivalId($festivalId)) === $price)) {
                $result[] = $item;
            }
        }

        return $result;
    }
}
