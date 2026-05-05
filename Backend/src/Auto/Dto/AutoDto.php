<?php

declare(strict_types=1);

namespace Tickets\Auto\Dto;

use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

/**
 * Авто, привязанное к заказу-списку.
 */
final class AutoDto extends AbstractionEntity
{
    public function __construct(
        public readonly Uuid    $id,
        public readonly Uuid    $orderTicketId,
        public readonly string  $number,
        public readonly ?string $createdAt = null,
    ) {
    }

    public static function create(Uuid $orderTicketId, string $number): self
    {
        return new self(
            Uuid::random(),
            $orderTicketId,
            trim($number),
        );
    }

    public static function fromState(array $data): self
    {
        return new self(
            new Uuid($data['id']),
            new Uuid($data['order_ticket_id']),
            (string) $data['number'],
            isset($data['created_at']) ? (string) $data['created_at'] : null,
        );
    }

    public function toArrayForCreate(): array
    {
        return [
            'id'              => $this->id->value(),
            'order_ticket_id' => $this->orderTicketId->value(),
            'number'          => $this->number,
        ];
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id->value(),
            'order_ticket_id' => $this->orderTicketId->value(),
            'number'          => $this->number,
            'created_at'      => $this->createdAt,
        ];
    }
}
