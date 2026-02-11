<?php

declare(strict_types=1);

namespace Shared\Questionnaire\Domain;

use Shared\Domain\Aggregate\AggregateRoot;
use Shared\Questionnaire\Domain\DomainEvent\ProcessInviteLinkQuestionnaire;
use Shared\Questionnaire\Domain\DomainEvent\ProcessTelegramSend;
use Shared\Questionnaire\Dto\QuestionnaireTicketDto;

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

    public static function toSendTelegram(QuestionnaireTicketDto $questionnaireTicketDto): self
    {
        $result = new self($questionnaireTicketDto);

        if($questionnaireTicketDto->getTelegram()) {
            $result->record(new ProcessTelegramSend($questionnaireTicketDto->getTelegram()));
        }
        return $result;
    }
}
