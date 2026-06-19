<?php

declare(strict_types=1);

namespace Tickets\BazaDelivery\Domain;

use Tickets\History\Domain\HistoryEventInterface;

/**
 * Событие жизненного цикла доставки билета в Baza (таблица domain_history,
 * aggregate_type = 'baza_delivery').
 *
 * event_name = 'baza_' . статус (baza_queued / baza_sending / baza_delivered / baza_failed).
 * payload — без ПДн (статус/ошибка/target), не ФИО/email.
 *
 * Зеркало EmailLifecycleEvent. История пишется на КАЖДУЮ попытку доставки.
 */
final class BazaDeliveryLifecycleEvent implements HistoryEventInterface
{
    /**
     * @param string $status статус доставки (BazaDeliveryStatus::*)
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly string $status,
        private readonly array $payload = [],
    ) {
    }

    public function getAggregateType(): string
    {
        return 'baza_delivery';
    }

    public function getEventName(): string
    {
        return 'baza_' . $this->status;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
