<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Dto\OrderTicket;

use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Uuid;

final class TypesOfPaymentDto extends AbstractionEntity
{
    public function __construct(
        protected Uuid $id,
        protected string $name,
    ) {
    }

    public static function fromState(array $data): self
    {
        return new self(
            new Uuid($data['id']),
            $data['name']
        );
    }

    public function getName(): string
    {
        return $this->name;
    }
}
