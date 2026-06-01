<?php

declare(strict_types=1);

namespace Tickets\OptionPrice\Application\GetList;

use App\Models\Option\OptionPriceModel;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Tickets\OptionPrice\Repositories\OptionPriceRepositoryInterface;
use Tickets\OptionPrice\Response\OptionPriceGetListResponse;

class OptionPriceGetListQueryHandler implements QueryHandler
{
    public function __construct(
        private OptionPriceRepositoryInterface $repository
    ) {
    }

    public function __invoke(OptionPriceGetListQuery $query): OptionPriceGetListResponse
    {
        $filter = $query->getFilter();
        $filters = Filters::fromValues($this->getFilterValues($filter));

        return new OptionPriceGetListResponse(
            $this->repository->getList($filters, $query->getOrderBy())
        );
    }

    private function getFilterValues(array $filter): array
    {
        return [
            [
                'field' => OptionPriceModel::TABLE.'.option_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['option_id'] ?? null,
            ],
        ];
    }
}
