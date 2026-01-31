<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire\GetItem;

use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Questionnaire\Repositories\QuestionnaireRepositoryInterface;
use Tickets\Questionnaire\Responses\QuestionnaireGetListQueryResponse;

class QuestionnaireGetItemQueryHandler implements QueryHandler
{
    public function __construct(
        private QuestionnaireRepositoryInterface $repository
    )
    {
    }

    public function __invoke(QuestionnaireGetItemQuery $query): QuestionnaireGetListQueryResponse
    {
        return $this->repository->getByOrderId(
            $query->getOrderId(),
            $query->getTicketId(),
        );
    }
}
