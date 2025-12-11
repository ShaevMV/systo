<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Questionnaire\Create;

use Tickets\Order\OrderTicket\Repositories\QuestionnaireRepositoryInterface;

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
