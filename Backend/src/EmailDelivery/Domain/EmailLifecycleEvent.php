<?php

declare(strict_types=1);

namespace Tickets\EmailDelivery\Domain;

use Tickets\History\Domain\HistoryEventInterface;

/**
 * Событие жизненного цикла письма (таблица domain_history, aggregate_type = 'email').
 *
 * event_name = 'email_' . статус (email_queued / email_sending / email_sent / email_failed /
 * email_opened / ...). payload — без ПДн (статус/событие/ошибка), не email/ФИО.
 */
final class EmailLifecycleEvent implements HistoryEventInterface
{
    /**
     * @param string $status статус письма (EmailStatus::*)
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly string $status,
        private readonly array $payload = [],
    ) {
    }

    public function getAggregateType(): string
    {
        return 'email';
    }

    public function getEventName(): string
    {
        return 'email_' . $this->status;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
