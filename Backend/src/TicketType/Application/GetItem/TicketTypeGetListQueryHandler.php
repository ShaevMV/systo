<?php

declare(strict_types=1);

namespace Tickets\TicketType\Application\GetItem;

use App\Models\Festival\TicketTypesModel;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Tickets\TicketType\Repository\TicketTypeRepositoryInterface;
use Tickets\TicketType\Response\TicketTypeGetListResponse;

class TicketTypeGetListQueryHandler implements QueryHandler
{
    public function __construct(
        private TicketTypeRepositoryInterface $repository
    )
    {
    }

    public function __invoke(TicketTypeGetListQuery $query): TicketTypeGetListResponse
    {
        return new TicketTypeGetListResponse(
            $this->repository->getList(Filters::fromValues($this->getFilterValues($query)))
        );
    }


    private function getFilterValues(TicketTypeGetListQuery $filterQuery): array
    {
        return [
            // email
            [
                'field' => TicketTypesModel::TABLE . '.name',
                'operator' => FilterOperator::LIKE,
                'value' => '%'. $filterQuery->getName().'%',
            ],
            // status
            [
                'field' => TicketTypesModel::TABLE . '.price',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getPrice(),
            ],
            // types_of_payment_id
            [
                'field' => TicketTypesModel::TABLE . '.active',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getActive(),
            ],
            [
                'field' => TicketTypesModel::TABLE . '.is_live_ticket',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getIsLiveTicket(),
            ],
        ];
    }
}
