<?php

declare(strict_types=1);

namespace Tickets\QuestionnaireType\Application\GetList;

use App\Models\Questionnaire\QuestionnaireTypeModel;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Tickets\QuestionnaireType\Repositories\QuestionnaireTypeRepositoryInterface;
use Tickets\QuestionnaireType\Response\QuestionnaireTypeGetListResponse;

class QuestionnaireTypeGetListQueryHandler implements QueryHandler
{
    public function __construct(
        private QuestionnaireTypeRepositoryInterface $repository
    )
    {
    }

    public function __invoke(QuestionnaireTypeGetListQuery $query): QuestionnaireTypeGetListResponse
    {
        $filter = $query->getFilter();
        $filters = Filters::fromValues($this->getFilterValues($filter));

        return new QuestionnaireTypeGetListResponse(
            $this->repository->getList($filters, $query->getOrderBy())
        );
    }

    private function getFilterValues(array $filter): array
    {
        return [
            [
                'field' => QuestionnaireTypeModel::TABLE . '.name',
                'operator' => FilterOperator::LIKE,
                'value' => $filter['name'] ?? null,
            ],
            [
                'field' => QuestionnaireTypeModel::TABLE . '.active',
                'operator' => FilterOperator::EQUAL,
                'value' => $filter['active'] ?? null,
            ],
        ];
    }
}
