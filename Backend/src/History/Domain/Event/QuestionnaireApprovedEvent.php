<?php

declare(strict_types=1);

namespace Tickets\History\Domain\Event;

use Tickets\History\Domain\HistoryEventInterface;

/**
 * Анкета гостя одобрена администратором (domain_history, aggregate_type = 'questionnaire').
 *
 * Payload без ПДн — только идентификаторы связи (заказ/билет/тип анкеты), не email/ФИО.
 */
final class QuestionnaireApprovedEvent implements HistoryEventInterface
{
    public function __construct(
        private ?string $orderId = null,
        private ?string $ticketId = null,
        private ?string $questionnaireTypeId = null,
    ) {
    }

    public function getAggregateType(): string
    {
        return 'questionnaire';
    }

    public function getEventName(): string
    {
        return 'questionnaire_approved';
    }

    public function getPayload(): array
    {
        return array_filter([
            'order_id' => $this->orderId,
            'ticket_id' => $this->ticketId,
            'questionnaire_type_id' => $this->questionnaireTypeId,
        ], static fn ($value): bool => $value !== null);
    }
}
