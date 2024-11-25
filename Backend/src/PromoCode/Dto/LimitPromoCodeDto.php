<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Dto;

use Shared\Domain\Entity\AbstractionEntity;

class LimitPromoCodeDto extends AbstractionEntity
{
    public function __construct(
        protected int  $count = 0,
        protected ?int $limit = null
    )
    {
    }

    public static function fromState(array $data): self
    {
        return new self(
            $data['countUses'] ?? 0,
            $data['limit'] ?? null,
        );
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }


    public function getCorrect(): bool
    {
        if (is_null($this->limit)) {
            return true;
        }

        return $this->limit > $this->count;
    }
}
