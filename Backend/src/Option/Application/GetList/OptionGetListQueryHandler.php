<?php

declare(strict_types=1);

namespace Tickets\Option\Application\GetList;

use App\Models\Option\OptionModel;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Tickets\Option\Repositories\OptionRepositoryInterface;
use Tickets\Option\Response\OptionGetListResponse;

class OptionGetListQueryHandler implements QueryHandler
{
    public function __construct(
        private OptionRepositoryInterface $repository
    ) {
    }

    public function __invoke(OptionGetListQuery $query): OptionGetListResponse
    {
        $filter = $query->getFilter();
        $filters = Filters::fromValues($this->getFilterValues($filter));

        return new OptionGetListResponse(
            $this->repository->getList($filters, $query->getOrderBy())
        );
    }

    private function getFilterValues(array $filter): array
    {
        return [
            [
                'field' => OptionModel::TABLE . '.name',
                'operator' => FilterOperator::LIKE,
                'value' => $filter['name'] ?? null,
            ],
            [
                'field' => OptionModel::TABLE . '.festival_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['festival_id'] ?? null,
            ],
            [
                'field' => OptionModel::TABLE . '.active',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['active'] ?? null,
            ],
        ];
    }
}
