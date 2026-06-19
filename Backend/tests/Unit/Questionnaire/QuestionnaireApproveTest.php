<?php

declare(strict_types=1);

namespace Tests\Unit\Questionnaire;

use Tests\TestCase;
use Tickets\History\Domain\Event\QuestionnaireApprovedEvent;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessQuestionnaireApprovedNotification;
use Tickets\Questionnaire\Domain\Questionnaire;
use Tickets\Questionnaire\Dto\QuestionnaireTicketDto;

/**
 * Доменная логика одобрения анкеты: toApprove записывает письмо «анкета одобрена» гостю
 * (ProcessQuestionnaireApprovedNotification) и событие истории QuestionnaireApprovedEvent.
 * Без email письмо не записывается, но история — да.
 */
class QuestionnaireApproveTest extends TestCase
{
    public function test_to_approve_records_notification_and_history(): void
    {
        $dto = QuestionnaireTicketDto::fromState([
            'email' => 'guest@example.com',
            'order_id' => '11111111-1111-4111-8111-111111111111',
            'ticket_id' => '22222222-2222-4222-8222-222222222222',
            'questionnaire_type_id' => '33333333-3333-4333-8333-333333333333',
            'id' => 7,
        ]);

        $questionnaire = Questionnaire::toApprove($dto);

        $domainEvents = $questionnaire->pullDomainEvents();
        $this->assertCount(1, $domainEvents);
        $this->assertInstanceOf(ProcessQuestionnaireApprovedNotification::class, $domainEvents[0]);

        $historyEvents = $questionnaire->pullHistoryEvents();
        $this->assertCount(1, $historyEvents);
        $this->assertInstanceOf(QuestionnaireApprovedEvent::class, $historyEvents[0]);
        $this->assertSame('questionnaire', $historyEvents[0]->getAggregateType());
        $this->assertSame('questionnaire_approved', $historyEvents[0]->getEventName());
    }

    public function test_to_approve_without_email_skips_notification_but_records_history(): void
    {
        $dto = QuestionnaireTicketDto::fromState(['id' => 8]);

        $questionnaire = Questionnaire::toApprove($dto);

        $this->assertCount(0, $questionnaire->pullDomainEvents());
        $this->assertCount(1, $questionnaire->pullHistoryEvents());
    }
}
