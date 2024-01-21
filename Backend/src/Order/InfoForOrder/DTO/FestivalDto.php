<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\DTO;

use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

class FestivalDto extends AbstractionEntity
{
    public function __construct(
        protected Uuid $id,
        protected int $year,
        protected string $name,
        protected bool $active = false,
    )
    {
    }

    public static function fromState(array $data): self
    {
        return new self(
            new Uuid($data['id']),
            $data['year'],
            $data['name'],
            (bool) $data['active'],
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}
