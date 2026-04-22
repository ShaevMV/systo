<?php

declare(strict_types=1);

namespace Tickets\QuestionnaireType\Application\GetByCode;

use DomainException;
use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\QuestionnaireType\Dto\QuestionnaireTypeDto;
use Tickets\QuestionnaireType\Repositories\QuestionnaireTypeRepositoryInterface;

class QuestionnaireTypeGetByCodeQueryHandler implements QueryHandler
{
    public function __construct(
        private QuestionnaireTypeRepositoryInterface $repository
    )
    {
    }

    public function __invoke(QuestionnaireTypeGetByCodeQuery $query): QuestionnaireTypeDto
    {
        return $this->repository->getByCode($query->getCode());
    }
}
