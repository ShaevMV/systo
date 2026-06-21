<?php

declare(strict_types=1);

namespace Baza\Tickets\Responses;

use Baza\Tickets\ValueObject\Color;

/**
 * Один элемент офлайн-снимка билетов для PWA-сканера (Ф5, PR-3).
 *
 * Жёсткая минимизация ПДн (решение B5): несём ТОЛЬКО поля впуска —
 * uuid/kilter/тип/цвет браслета/имя + festival_id + updated_at (курсор дельты).
 * НЕ несём телефон/email/telegram/госномер/детские данные — они только онлайн по тапу.
 */
final class SnapshotItemResponse
{
    public function __construct(
        private readonly string $uuid,        // ticket_uuid (id билета в org)
        private readonly ?int $kilter,        // номер билета — мост к впуску
        private readonly string $type,        // electron|spisok|live|auto (DefineService)
        private readonly string $color,       // цвет браслета по типу (Color)
        private readonly ?string $typeTicket, // читаемый тип билета
        private readonly ?string $name,       // ФИО гостя (для сверки на входе)
        private readonly ?string $festivalId,
        private readonly ?string $updatedAt,  // ISO — курсор дельты на клиенте
    ) {}

    /**
     * @param  array<string, mixed>  $row  строка ticket_search (toArray модели)
     */
    public static function fromState(array $row): self
    {
        $type = (string) ($row['type'] ?? '');

        return new self(
            (string) ($row['ticket_uuid'] ?? ''),
            isset($row['kilter']) ? (int) $row['kilter'] : null,
            $type,
            self::colorByType($type),
            $row['type_ticket'] ?? null,
            $row['fio'] ?? null,
            $row['festival_id'] ?? null,
            isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }

    /** Цвет браслета по типу билета (та же карта, что в scan-ответах). */
    private static function colorByType(string $type): string
    {
        return match ($type) {
            'auto' => Color::COLOR_AUTO,
            'spisok' => Color::COLOR_SPISOK,
            'live' => Color::COLOR_LIVE,
            default => Color::COLOR_ELECTRON, // electron/friendly и неизвестные
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'kilter' => $this->kilter,
            'type' => $this->type,
            'color' => $this->color,
            'type_ticket' => $this->typeTicket,
            'name' => $this->name,
            'festival_id' => $this->festivalId,
            'updated_at' => $this->updatedAt,
        ];
    }
}
