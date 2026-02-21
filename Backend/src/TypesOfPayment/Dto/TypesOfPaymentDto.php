<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Dto;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

class TypesOfPaymentDto extends AbstractionEntity implements Response
{
    public function __construct(
        protected string $name,
        protected bool $active,
        protected int $sort,
        protected bool $is_billing,
        protected ?string $card = null,
        protected ?Uuid $user_external_id = null,
        protected ?Uuid $id = null,
    )
    {
    }

    public static function fromState(array $data): self
    {
        return new self(
            $data['name'],
            boolval($data['active']),
            $data['sort'],
            boolval($data['is_billing']),
            $data['card'],
            empty($data['user_external_id']) ? null : new Uuid($data['user_external_id']),
            empty($data['id']) ? null : new Uuid($data['id']),
        );
    }
}
