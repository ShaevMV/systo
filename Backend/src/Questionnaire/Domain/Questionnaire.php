<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Domain;

use Shared\Domain\Aggregate\AggregateRoot;
use Tickets\History\Domain\Event\QuestionnaireApprovedEvent;
use Tickets\History\Trait\HasHistory;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessQuestionnaireApprovedNotification;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessTelegramSend;
use Tickets\Questionnaire\Dto\QuestionnaireTicketDto;

class Questionnaire extends AggregateRoot
{
    use HasHistory;

    public function __construct(
        QuestionnaireTicketDto $questionnaireTicketDto
    )
    {
    }

    /**
     * Одобрение анкеты: письмо гостю «анкета одобрена» (EmailEvent::QUESTIONNAIRE_APPROVED)
     * + запись факта одобрения в историю (domain_history, aggregate_type = 'questionnaire').
     *
     * Письмо шлётся только при наличии email у анкеты (без получателя слать некуда).
     */
    public static function toApprove(QuestionnaireTicketDto $questionnaireTicketDto): self
    {
        $result = new self($questionnaireTicketDto);

        if ($questionnaireTicketDto->getEmail()) {
            $result->record(new ProcessQuestionnaireApprovedNotification(
                    $questionnaireTicketDto->getEmail(),
                )
            );
        }

        $result->recordHistory(new QuestionnaireApprovedEvent(
            $questionnaireTicketDto->getOrderId()?->value(),
            $questionnaireTicketDto->getTicketId()?->value(),
            $questionnaireTicketDto->getQuestionnaireTypeId()?->value(),
        ));

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
