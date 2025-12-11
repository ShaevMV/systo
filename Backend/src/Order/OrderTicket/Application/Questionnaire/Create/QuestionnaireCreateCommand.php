<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Questionnaire\Create;

use Shared\Domain\Bus\Command\Command;
use Tickets\Order\OrderTicket\Dto\OrderTicket\QuestionnaireTicketDto;

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
