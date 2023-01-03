<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Response;

use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Uuid;

final class TicketTypeDto extends AbstractionEntity implements Response
{
    public function __construct(
        protected Uuid $id,
        protected string $name,
        protected float $price,
        protected ?int $groupLimit = null,
    ) {
    }

    public static function fromState(array $data): self
    {
        $groupLimit = isset($data['groupLimit']) && !empty($data['groupLimit']) ? (int)$data['groupLimit'] : null;

        return new self(
            new Uuid($data['id']),
            $data['name'],
            (float) $data['price'],
            $groupLimit
        );
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getGroupLimit(): ?int
    {
        return $this->groupLimit;
    }
}
