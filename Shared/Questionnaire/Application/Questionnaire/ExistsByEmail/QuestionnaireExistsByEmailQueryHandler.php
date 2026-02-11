<?php

declare(strict_types=1);

namespace Shared\Questionnaire\Application\Questionnaire\ExistsByEmail;

use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Questionnaire\Repositories\QuestionnaireRepositoryInterface;

class QuestionnaireExistsByEmailQueryHandler implements QueryHandler
{
    public function __construct(
        private QuestionnaireRepositoryInterface $repository
    )
    {
    }

    public function __invoke(QuestionnaireExistsByEmailQuery $query): bool
    {
        return $this->repository->existByEmail($query->getEmail());
    }
}
