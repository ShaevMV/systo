<?php

declare(strict_types=1);

namespace Tickets\QuestionnaireType\Application\GetList;

use Shared\Domain\Bus\Query\QueryHandler;
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
        $filters = Filters::fromValues($query->getFilter());

        return new QuestionnaireTypeGetListResponse(
            $this->repository->getList($filters, $query->getOrderBy())
        );
    }
}
