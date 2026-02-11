<?php

declare(strict_types=1);

namespace Shared\Questionnaire\Application\Questionnaire\Create;

use Shared\Domain\Bus\Command\Command;
use Shared\Questionnaire\Dto\QuestionnaireTicketDto;

class QuestionnaireCreateCommand implements Command
{
    public function __construct(
        private QuestionnaireTicketDto $questionnaireTicketDto
    )
    {
    }

    public function getQuestionnaireTicketDto(): QuestionnaireTicketDto
    {
        return $this->questionnaireTicketDto;
    }
}
