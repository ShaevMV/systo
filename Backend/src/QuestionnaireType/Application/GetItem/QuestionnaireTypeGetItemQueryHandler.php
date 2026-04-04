<?php

declare(strict_types=1);

namespace Tickets\QuestionnaireType\Application\GetItem;

use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\QuestionnaireType\Dto\QuestionnaireTypeDto;
use Tickets\QuestionnaireType\Repositories\QuestionnaireTypeRepositoryInterface;

class QuestionnaireTypeGetItemQueryHandler implements QueryHandler
{
    public function __construct(
        private QuestionnaireTypeRepositoryInterface $repository
    )
    {
    }

    public function __invoke(QuestionnaireTypeGetItemQuery $query): QuestionnaireTypeDto
    {
        return $this->repository->getItem($query->getId());
    }
}
