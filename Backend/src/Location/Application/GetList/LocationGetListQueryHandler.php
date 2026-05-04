<?php

declare(strict_types=1);

namespace Tickets\Location\Application\GetList;

use App\Models\Location\LocationModel;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Tickets\Location\Repositories\LocationRepositoryInterface;
use Tickets\Location\Response\LocationGetListResponse;

class LocationGetListQueryHandler implements QueryHandler
{
    public function __construct(
        private LocationRepositoryInterface $repository
    ) {
    }

    public function __invoke(LocationGetListQuery $query): LocationGetListResponse
    {
        $filter = $query->getFilter();
        $filters = Filters::fromValues($this->getFilterValues($filter));

        return new LocationGetListResponse(
            $this->repository->getList($filters, $query->getOrderBy())
        );
    }

    private function getFilterValues(array $filter): array
    {
        return [
            [
                'field' => LocationModel::TABLE . '.name',
                'operator' => FilterOperator::LIKE,
                'value' => $filter['name'] ?? null,
            ],
            [
                'field' => LocationModel::TABLE . '.festival_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['festival_id'] ?? null,
            ],
            [
                'field' => LocationModel::TABLE . '.active',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['active'] ?? null,
            ],
        ];
    }
}
