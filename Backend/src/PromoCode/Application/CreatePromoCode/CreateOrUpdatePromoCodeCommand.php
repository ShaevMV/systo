<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Application\CreatePromoCode;

use Tickets\Shared\Domain\Bus\Command\Command;
use Tickets\Shared\Domain\ValueObject\Uuid;

class CreateOrUpdatePromoCodeCommand implements Command
{
    public function __construct(
        private string $name,
        private float $discount,
        private bool $is_percent,
        private bool $active,
        private ?Uuid $id = null,
        private ?int $limit = null,

    )
    {
    }

    public static function fromState(array $data): self
    {
        $id = isset($data['id']) ? new Uuid($data['id']) : Uuid::random();

        return new self(
            $data['name'],
            $data['discount'],
            $data['is_percent'],
            $data['active'],
            $id,
            $data['limit'] ?? null,
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

    public function setId(?Uuid $id): CreateOrUpdatePromoCodeCommand
    {
        $this->id = $id;

        return $this;
    }
}
