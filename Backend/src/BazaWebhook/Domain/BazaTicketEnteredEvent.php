<?php

declare(strict_types=1);

namespace Tickets\BazaWebhook\Domain;

use Tickets\History\Domain\HistoryEventInterface;

/**
 * Событие истории «билет прошёл КПП» (Ф4): вебхук от Baza → запись в domain_history.
 *
 * aggregate_type = 'ticket' (aggregate_id = uuid билета в org), event_name = 'ticket_entered'.
 * Payload без ПДн — время входа/смена/цель/номер/QR браслета/ключ идемпотентности (не ФИО/email).
 */
final class BazaTicketEnteredEvent implements HistoryEventInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly array $payload = [],
    ) {}

    public function getAggregateType(): string
    {
        return 'ticket';
    }

    public function getEventName(): string
    {
        return 'ticket_entered';
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
