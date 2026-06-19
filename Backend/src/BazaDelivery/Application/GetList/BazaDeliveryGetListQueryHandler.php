<?php

declare(strict_types=1);

namespace Tickets\BazaDelivery\Application\GetList;

use App\Models\BazaDelivery\BazaDeliveryModel;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Tickets\BazaDelivery\Repositories\BazaDeliveryRepositoryInterface;
use Tickets\BazaDelivery\Responses\BazaDeliveryGetListResponse;

/**
 * Whitelist фильтров доставок в Baza: только перечисленные поля попадают в WHERE.
 */
class BazaDeliveryGetListQueryHandler implements QueryHandler
{
    public function __construct(
        private BazaDeliveryRepositoryInterface $repository,
    ) {
    }

    public function __invoke(BazaDeliveryGetListQuery $query): BazaDeliveryGetListResponse
    {
        $filters = Filters::fromValues($this->getFilterValues($query->getFilter()));

        return new BazaDeliveryGetListResponse(
            $this->repository->getList($filters, $query->getOrderBy(), $query->getPage(), $query->getPerPage()),
            $this->repository->countList($filters),
        );
    }

    /**
     * @param array<string, mixed> $filter
     * @return array<int, array{field: string, operator: string, value: mixed}>
     */
    private function getFilterValues(array $filter): array
    {
        return [
            [
                'field' => BazaDeliveryModel::TABLE . '.status',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['status'] ?? null,
            ],
            [
                'field' => BazaDeliveryModel::TABLE . '.target',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['target'] ?? null,
            ],
            [
                'field' => BazaDeliveryModel::TABLE . '.ticket_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['ticket_id'] ?? null,
            ],
            [
                'field' => BazaDeliveryModel::TABLE . '.order_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['order_id'] ?? null,
            ],
            [
                'field' => BazaDeliveryModel::TABLE . '.festival_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['festival_id'] ?? null,
            ],
            [
                'field' => BazaDeliveryModel::TABLE . '.source',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['source'] ?? null,
            ],
            [
                'field' => BazaDeliveryModel::TABLE . '.name',
                'operator' => FilterOperator::LIKE,
                'value' => $filter['name'] ?? null,
            ],
            [
                'field' => BazaDeliveryModel::TABLE . '.email',
                'operator' => FilterOperator::LIKE,
                'value' => $filter['email'] ?? null,
            ],
        ];
    }
}
