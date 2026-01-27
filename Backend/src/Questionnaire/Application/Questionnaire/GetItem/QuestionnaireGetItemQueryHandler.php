<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire\GetItem;

use QuestionnaireRepositoryInterface;
use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Questionnaire\Responses\QuestionnaireGetItemQueryResponse;

class QuestionnaireGetItemQueryHandler implements QueryHandler
{
    public function __construct(
        private QuestionnaireRepositoryInterface $repository
    )
    {
    }

    public function __invoke(QuestionnaireGetItemQuery $query): QuestionnaireGetItemQueryResponse
    {
        return $this->repository->getByOrderId($query->getOrderId());
    }
}
