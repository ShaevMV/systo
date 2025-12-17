<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Questionnaire\GetItem;

use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Order\OrderTicket\Repositories\QuestionnaireRepositoryInterface;
use Tickets\Order\OrderTicket\Responses\QuestionnaireGetItemQueryResponse;

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
