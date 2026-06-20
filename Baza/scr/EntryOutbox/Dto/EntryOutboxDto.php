<?php

declare(strict_types=1);

namespace Baza\EntryOutbox\Dto;

/**
 * Строка outbox вебхука входа (Ф4). Пассивный DTO для дренажа.
 */
final class EntryOutboxDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $target,
        public readonly ?string $ticketUuid,
        public readonly ?int $kilter,
        public readonly ?int $changeId,
        public readonly ?string $enteredAt,
        public readonly ?string $wristbandQr,
        public readonly string $status,
        public readonly int $attempts,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromState(array $row): self
    {
        return new self(
            (string) $row['id'],
            (string) $row['target'],
            isset($row['ticket_uuid']) ? (string) $row['ticket_uuid'] : null,
            isset($row['kilter']) ? (int) $row['kilter'] : null,
            isset($row['change_id']) ? (int) $row['change_id'] : null,
            isset($row['entered_at']) ? (string) $row['entered_at'] : null,
            isset($row['wristband_qr']) ? (string) $row['wristband_qr'] : null,
            (string) ($row['status'] ?? 'pending'),
            (int) ($row['attempts'] ?? 0),
        );
    }

    /**
     * Тело вебхука для org (POST /api/v1/baza/ticketEntered).
     *
     * @return array<string, mixed>
     */
    public function toWebhookPayload(): array
    {
        return [
            'event_id' => $this->id,          // ключ идемпотентности на стороне org
            'ticket_uuid' => $this->ticketUuid,
            'target' => $this->target,
            'kilter' => $this->kilter,
            'change_id' => $this->changeId,
            'entered_at' => $this->enteredAt,
            'wristband_qr' => $this->wristbandQr,
        ];
    }
}
