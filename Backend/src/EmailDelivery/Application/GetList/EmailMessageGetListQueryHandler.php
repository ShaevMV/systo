<?php

declare(strict_types=1);

namespace Tickets\EmailDelivery\Application\GetList;

use App\Models\EmailDelivery\EmailMessageModel;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Tickets\EmailDelivery\Repositories\EmailMessageRepositoryInterface;
use Tickets\EmailDelivery\Responses\EmailMessageGetListResponse;

/**
 * Whitelist фильтров писем: только перечисленные поля попадают в WHERE.
 */
class EmailMessageGetListQueryHandler implements QueryHandler
{
    public function __construct(
        private EmailMessageRepositoryInterface $repository,
    ) {
    }

    public function __invoke(EmailMessageGetListQuery $query): EmailMessageGetListResponse
    {
        $filters = Filters::fromValues($this->getFilterValues($query->getFilter()));

        return new EmailMessageGetListResponse(
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
                'field' => EmailMessageModel::TABLE . '.recipient',
                'operator' => FilterOperator::LIKE,
                'value' => $filter['recipient'] ?? null,
            ],
            [
                'field' => EmailMessageModel::TABLE . '.status',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['status'] ?? null,
            ],
            [
                'field' => EmailMessageModel::TABLE . '.event',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['event'] ?? null,
            ],
            [
                'field' => EmailMessageModel::TABLE . '.source',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['source'] ?? null,
            ],
            [
                'field' => EmailMessageModel::TABLE . '.festival_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['festival_id'] ?? null,
            ],
            [
                'field' => EmailMessageModel::TABLE . '.aggregate_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['aggregate_id'] ?? null,
            ],
        ];
    }
}
