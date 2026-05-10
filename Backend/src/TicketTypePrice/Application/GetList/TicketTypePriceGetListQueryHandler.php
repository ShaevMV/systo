<?php

declare(strict_types=1);

namespace Tickets\TicketTypePrice\Application\GetList;

use App\Models\Festival\TicketTypesPriceModel;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Tickets\TicketTypePrice\Repositories\TicketTypePriceRepositoryInterface;
use Tickets\TicketTypePrice\Response\TicketTypePriceGetListResponse;

class TicketTypePriceGetListQueryHandler implements QueryHandler
{
    public function __construct(
        private TicketTypePriceRepositoryInterface $repository
    ) {
    }

    public function __invoke(TicketTypePriceGetListQuery $query): TicketTypePriceGetListResponse
    {
        $filter = $query->getFilter();
        $filters = Filters::fromValues($this->getFilterValues($filter));

        return new TicketTypePriceGetListResponse(
            $this->repository->getList($filters, $query->getOrderBy())
        );
    }

    private function getFilterValues(array $filter): array
    {
        return [
            [
                'field' => TicketTypesPriceModel::TABLE . '.ticket_type_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['ticket_type_id'] ?? null,
            ],
        ];
    }
}
