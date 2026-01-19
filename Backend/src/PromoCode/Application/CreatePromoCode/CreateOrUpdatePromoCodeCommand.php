<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Application\CreatePromoCode;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;

class CreateOrUpdatePromoCodeCommand implements Command
{
    public function __construct(
        private string $name,
        private float  $discount,
        private bool   $is_percent,
        private bool   $active,
        private ?Uuid  $id = null,
        private ?int   $limit = null,
        private ?Uuid  $ticket_type_id = null,
        private ?Uuid  $festivalId = null,
    )
    {
    }

    public static function fromState(array $data): self
    {
        $id = isset($data['id']) ? new Uuid($data['id']) : Uuid::random();
        $ticketTypeId = !empty($data['ticket_type_id']) ? new Uuid($data['ticket_type_id']) : null;
        $festivalId = !empty($data['festival_id']) ? new Uuid($data['festival_id']) : null;
        return new self(
            $data['name'],
            $data['discount'],
            $data['is_percent'] ?? false,
            $data['active'],
            $id,
            $data['limit'] ?? null,
            $ticketTypeId,
            $festivalId
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }

    public function isPercent(): bool
    {
        return $this->is_percent;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(?Uuid $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getTicketTypeId(): ?Uuid
    {
        return $this->ticket_type_id;
    }

    public function getFestivalId(): ?Uuid
    {
        return $this->festivalId;
    }
}
