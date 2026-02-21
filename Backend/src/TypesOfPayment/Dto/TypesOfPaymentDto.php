<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Dto;

use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

class TypesOfPaymentDto extends AbstractionEntity
{
    public function __construct(
        protected string $name,
        protected bool $active,
        protected int $sort,
        protected bool $is_billing,
        protected ?string $card = null,
        protected ?Uuid $user_id = null,
    )
    {
    }

    public static function fromState(array $data): self
    {
        return new self(
            $data['name'],
            $data['active'],
            $data['sort'],
            $data['is_billing'],
            $data['card'],
            empty($data['user_id']) ? null : new Uuid($data['user_id']),
        );
    }
}
