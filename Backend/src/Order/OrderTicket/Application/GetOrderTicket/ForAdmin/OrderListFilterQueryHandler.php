<?php

namespace Tickets\Ordering\OrderTicket\Application\GetOrderTicket\ForAdmin;

use App\Models\Ordering\OrderTicketModel;
use App\Models\User;
use Tickets\Ordering\OrderTicket\Application\GetOrderTicket\ListResponse;
use Tickets\Ordering\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Shared\Domain\Criteria\FilterOperator;
use Tickets\Shared\Domain\Criteria\Filters;

class OrderListFilterQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository
    ){
    }

    public function __invoke(OrderFilterQuery $filterQuery): ?ListResponse
    {
       $filter = Filters::fromValues([
            // email
            [
                'field' => User::TABLE.'.email',
                'operator' => FilterOperator::LIKE,
                'value' => $filterQuery->getEmail(),
            ],
           // status
           [
                'field' => OrderTicketModel::TABLE.'.status',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getStatus(),
           ],
           // ticket_type_id
           [
               'field' => OrderTicketModel::TABLE.'.ticket_type_id',
               'operator' => FilterOperator::EQUAL,
               'value' => $filterQuery->getTypeOrder()?->value(),
           ],
           // types_of_payment_id
           [
               'field' => OrderTicketModel::TABLE.'.types_of_payment_id',
               'operator' => FilterOperator::EQUAL,
               'value' => $filterQuery->getTypesOfPayment()?->value(),
           ],
           [
               'field' => OrderTicketModel::TABLE.'.promo_code',
               'operator' => FilterOperator::LIKE,
               'value' => $filterQuery->getPromoCode(),
           ]
       ]);
       $orderTicketItem = $this->orderTicketRepository->getList($filter);

       return count($orderTicketItem) > 0 ? new ListResponse($orderTicketItem) : null;
    }
}
