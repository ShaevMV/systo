<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire\GetList;

use App\Models\Questionnaire\QuestionnaireModel;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Tickets\Questionnaire\Repositories\QuestionnaireRepositoryInterface;
use Tickets\Questionnaire\Responses\QuestionnaireGetListQueryResponse;

class QuestionnaireGetListQueryHandler implements QueryHandler
{
    public function __construct(
        private QuestionnaireRepositoryInterface $repository
    )
    {
    }


    public function __invoke(QuestionnaireGetListQuery $query): QuestionnaireGetListQueryResponse
    {
        $filter = Filters::fromValues($this->getFilterValues($query));

        return new QuestionnaireGetListQueryResponse($this->repository->getList($filter));
    }


    private function getFilterValues(QuestionnaireGetListQuery $filterQuery): array
    {
        return [
            // email
            [
                'field' => QuestionnaireModel::TABLE . '.email',
                'operator' => FilterOperator::LIKE,
                'value' => $filterQuery->getEmail(),
            ],
            // status
            [
                'field' => QuestionnaireModel::TABLE . '.status',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getStatus(),
            ],
            [
                'field' => QuestionnaireModel::TABLE . '.telegram',
                'operator' => FilterOperator::LIKE,
                'value' => $filterQuery->getTelegram(),
            ],
            [
                'field' => 'data->vk',
                'operator' => FilterOperator::LIKE,
                'value' => $filterQuery->getVk(),
            ],
            [
                'field' => 'data->is_have_in_club',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getIsHaveInClub(),
            ],
        ];
    }
}
