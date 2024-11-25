<?php

namespace Tickets\Order\OrderTicket\Dto\Festival;


use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

class FestivalDto extends AbstractionEntity
{
    public function __construct(
        protected Uuid $id,
        protected string $name,
        protected int $year,
        protected bool $active,
    )
    {
    }


    public static function fromState(array $data): self
    {
        return new self(
            new Uuid($data['id']),
            $data['name'],
            $data['year'],
            $data['active']
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getYear(): int
    {
        return $this->year;
    }
}
