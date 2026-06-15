<?php

declare(strict_types=1);

namespace Tickets\Template\Application\GetList;

use App\Models\Template\TemplateModel;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Tickets\Template\Repositories\TemplateRepositoryInterface;
use Tickets\Template\Response\TemplateGetListResponse;

class TemplateGetListQueryHandler implements QueryHandler
{
    public function __construct(
        private TemplateRepositoryInterface $repository,
    ) {
    }

    public function __invoke(TemplateGetListQuery $query): TemplateGetListResponse
    {
        $filters = Filters::fromValues($this->getFilterValues($query->getFilter()));

        return new TemplateGetListResponse(
            $this->repository->getList($filters, $query->getOrderBy()),
        );
    }

    /**
     * Разрешённые поля фильтрации (whitelist). null-значения FilterBuilder пропускает.
     *
     * @param array<string, mixed> $filter
     * @return array<int, array{field: string, operator: string, value: mixed}>
     */
    private function getFilterValues(array $filter): array
    {
        return [
            [
                'field' => TemplateModel::TABLE . '.kind',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['kind'] ?? null,
            ],
            [
                'field' => TemplateModel::TABLE . '.slug',
                'operator' => FilterOperator::LIKE,
                'value' => $filter['slug'] ?? null,
            ],
            [
                'field' => TemplateModel::TABLE . '.title',
                'operator' => FilterOperator::LIKE,
                'value' => $filter['title'] ?? null,
            ],
            // NB: FilterBuilder отбрасывает значения через !empty() → active=false здесь НЕ отфильтрует
            // (известный паттерн-баг билдера). Фильтр по статусу де-факто работает только для true.
            [
                'field' => TemplateModel::TABLE . '.active',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['active'] ?? null,
            ],
        ];
    }
}
