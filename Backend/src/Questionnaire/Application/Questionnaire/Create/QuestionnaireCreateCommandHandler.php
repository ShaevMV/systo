<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire\Create;

use Tickets\Questionnaire\Repositories\QuestionnaireRepositoryInterface;

class QuestionnaireCreateCommandHandler
{
    public function __construct(
        private QuestionnaireRepositoryInterface $repository
    )
    {
    }

    public function __invoke(QuestionnaireCreateCommand $command): void
    {
        $this->repository->create($command->getQuestionnaireTicketDto());
    }
}
