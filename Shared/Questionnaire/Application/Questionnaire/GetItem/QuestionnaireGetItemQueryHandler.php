<?php

declare(strict_types=1);

namespace Shared\Questionnaire\Application\Questionnaire\GetItem;

use Illuminate\Support\Collection;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Questionnaire\Repositories\QuestionnaireRepositoryInterface;
use Shared\Questionnaire\Responses\QuestionnaireGetListQueryResponse;

class QuestionnaireGetItemQueryHandler implements QueryHandler
{
    public function __construct(
        private QuestionnaireRepositoryInterface $repository
    )
    {
    }

    public function __invoke(QuestionnaireGetItemQuery $query): QuestionnaireGetListQueryResponse
    {
        return new QuestionnaireGetListQueryResponse(new Collection($this->repository->get(
            $query->getId()
        )));
    }
}
