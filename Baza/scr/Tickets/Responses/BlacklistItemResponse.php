<?php

declare(strict_types=1);

namespace Baza\Tickets\Responses;

/**
 * Один отозванный билет в синке blacklist (Ф5, PR-6, B6). БЕЗ ПДн — только идентификаторы.
 */
final class BlacklistItemResponse
{
    public function __construct(
        private readonly ?string $uuid,
        private readonly ?int $kilter,
        private readonly ?string $reason,
        private readonly ?string $festivalId,
        private readonly ?string $updatedAt,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromState(array $row): self
    {
        return new self(
            isset($row['ticket_uuid']) ? (string) $row['ticket_uuid'] : null,
            isset($row['kilter']) ? (int) $row['kilter'] : null,
            $row['reason'] ?? null,
            $row['festival_id'] ?? null,
            isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'kilter' => $this->kilter,
            'reason' => $this->reason,
            'festival_id' => $this->festivalId,
            'updated_at' => $this->updatedAt,
        ];
    }
}
