<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Domain;

use Tickets\History\Domain\HistoryEventInterface;

/**
 * Событие истории заказа qr (таблица domain_history, aggregate_type = 'qr_order').
 *
 * Универсальное: имя события (created / status_changed / issued) + payload (без ПДн —
 * статусы/тип заказа/стратегия, но не email/телефон/ФИО).
 */
final class QrOrderHistoryEvent implements HistoryEventInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly string $eventName,
        private readonly array $payload = [],
    ) {
    }

    public function getAggregateType(): string
    {
        return 'qr_order';
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
