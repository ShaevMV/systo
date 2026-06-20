<?php

declare(strict_types=1);

namespace Baza\Tickets\Responses;

/**
 * Результат поиска в индексе ticket_search (ручной поиск на КПП без QR).
 * Несёт богатые поля для показа карточки + type/kilter для моста к впуску (enterForTable).
 */
final class TicketSearchResponse
{
    public function __construct(
        private readonly string $type,        // DefineService: electron|spisok|live|auto
        private readonly ?int $kilter,
        private readonly ?string $fio,
        private readonly ?string $phone,
        private readonly ?string $telegram,
        private readonly ?string $email,
        private readonly ?string $city,
        private readonly ?string $carNumber,
        private readonly ?string $childName,
        private readonly ?string $parentPhone,
        private readonly ?string $externalOrderNo,
        private readonly ?string $typeTicket,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromState(array $row): self
    {
        return new self(
            (string) ($row['type'] ?? ''),
            isset($row['kilter']) ? (int) $row['kilter'] : null,
            $row['fio'] ?? null,
            $row['phone'] ?? null,
            $row['telegram'] ?? null,
            $row['email'] ?? null,
            $row['city'] ?? null,
            $row['car_number'] ?? null,
            $row['child_name'] ?? null,
            $row['parent_phone'] ?? null,
            $row['external_order_no'] ?? null,
            $row['type_ticket'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'kilter' => $this->kilter,
            'fio' => $this->fio,
            'phone' => $this->phone,
            'telegram' => $this->telegram,
            'email' => $this->email,
            'city' => $this->city,
            'car_number' => $this->carNumber,
            'child_name' => $this->childName,
            'parent_phone' => $this->parentPhone,
            'external_order_no' => $this->externalOrderNo,
            'type_ticket' => $this->typeTicket,
        ];
    }
}
