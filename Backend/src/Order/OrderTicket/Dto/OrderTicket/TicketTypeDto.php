<?php

declare(strict_types =1);

namespace Tickets\Order\OrderTicket\Dto\OrderTicket;

use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

final class TicketTypeDto extends AbstractionEntity
{
    public function __construct(
        protected Uuid $id,
        protected string $name,
        protected float $price,
        protected ?int $gropeLimit = null,
        protected array $festivalList = [],
    ) {
    }


    public static function fromState(array $data): self
    {
        return new self(
            new Uuid($data['id']),
            $data['name'],
            (float)$data['price'],
            $data['groupLimit'],
            $data[]
        );
    }

    public function getName(): string
    {
        return $this->name;
    }
}
