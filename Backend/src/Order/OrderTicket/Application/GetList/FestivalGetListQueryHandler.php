<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetList;

use App\Models\Festival\FestivalModel;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Tickets\Order\OrderTicket\Repositories\FestivalRepositoryInterface;
use Tickets\Order\OrderTicket\Responses\FestivalGetListResponse;

class FestivalGetListQueryHandler implements QueryHandler
{
    public function __construct(
        private FestivalRepositoryInterface $repository
    ) {
    }

    public function __invoke(FestivalGetListQuery $query): FestivalGetListResponse
    {
        $filters = Filters::fromValues($this->getFilterValues($query->getFilter()));

        return new FestivalGetListResponse(
            $this->repository->getList($filters, $query->getOrderBy())
        );
    }

    private function getFilterValues(array $filter): array
    {
        return [
            [
                'field' => FestivalModel::TABLE . '.name',
                'operator' => FilterOperator::LIKE,
                'value' => $filter['name'] ?? null,
            ],
            [
                'field' => FestivalModel::TABLE . '.year',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['year'] ?? null,
            ],
            [
                'field' => FestivalModel::TABLE . '.active',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['active'] ?? null,
            ],
        ];
    }
}
