<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Domain;

use Shared\Domain\Aggregate\AggregateRoot;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderPaid;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessInviteLinkQuestionnaire;
use Tickets\Questionnaire\Dto\QuestionnaireTicketDto;

class Questionnaire extends AggregateRoot
{
    public function __construct(
        QuestionnaireTicketDto $questionnaireTicketDto
    )
    {
    }

    public static function toApprove(QuestionnaireTicketDto $questionnaireTicketDto): self
    {
        $result = new self($questionnaireTicketDto);
        $result->record(new ProcessInviteLinkQuestionnaire(
                $questionnaireTicketDto->getEmail(),
            )
        );

        return $result;
    }
}
