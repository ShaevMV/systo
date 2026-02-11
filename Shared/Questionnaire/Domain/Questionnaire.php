<?php

declare(strict_types=1);

namespace Shared\Questionnaire\Domain;

use App\Jobs\ProcessTelegramSend;
use Shared\Domain\Aggregate\AggregateRoot;
use Shared\Questionnaire\Domain\DomainEvent\ProcessInviteLinkQuestionnaire;
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
        \Log::info('21321321');
        if($questionnaireTicketDto->getTelegram()) {
            $result->record(new ProcessTelegramSend($questionnaireTicketDto->getTelegram()));
        }


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
