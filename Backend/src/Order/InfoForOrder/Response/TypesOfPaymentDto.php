<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Response;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

final class TypesOfPaymentDto extends AbstractionEntity implements Response
{
    public function __construct(
        public Uuid $id,
        public string $name,
    ) {
    }

    public static function fromState(array $data): self
    {
        return new self(
            new Uuid($data['id']),
            $data['name']
        );
    }
}
