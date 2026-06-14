<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\GetList;

use App\Models\QrOrder\QrOrderModel;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Tickets\QrOrder\Repositories\QrOrderRepositoryInterface;
use Tickets\QrOrder\Responses\QrOrderGetListResponse;

/**
 * Строит whitelist фильтров из произвольного тела запроса и отдаёт страницу заказов + total.
 * Только разрешённые поля попадают в WHERE — клиент не может фильтровать по чему угодно.
 */
class QrOrderGetListQueryHandler implements QueryHandler
{
    public function __construct(
        private QrOrderRepositoryInterface $repository,
    ) {
    }

    public function __invoke(QrOrderGetListQuery $query): QrOrderGetListResponse
    {
        $filters = Filters::fromValues($this->getFilterValues($query->getFilter()));

        return new QrOrderGetListResponse(
            $this->repository->getList($filters, $query->getOrderBy(), $query->getPage(), $query->getPerPage()),
            $this->repository->countList($filters),
        );
    }

    /**
     * Разрешённые поля фильтрации. null-значения FilterBuilder пропускает (нет условия).
     *
     * @param array<string, mixed> $filter
     * @return array<int, array{field: string, operator: string, value: mixed}>
     */
    private function getFilterValues(array $filter): array
    {
        return [
            [
                'field' => QrOrderModel::TABLE . '.email',
                'operator' => FilterOperator::LIKE,
                'value' => $filter['email'] ?? null,
            ],
            [
                'field' => QrOrderModel::TABLE . '.status',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['status'] ?? null,
            ],
            [
                'field' => QrOrderModel::TABLE . '.festival_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['festival_id'] ?? null,
            ],
            [
                'field' => QrOrderModel::TABLE . '.type_order',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['type_order'] ?? null,
            ],
            [
                'field' => QrOrderModel::TABLE . '.city',
                'operator' => FilterOperator::LIKE,
                'value' => $filter['city'] ?? null,
            ],
        ];
    }
}
