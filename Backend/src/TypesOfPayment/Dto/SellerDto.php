<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Dto;

use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

class SellerDto extends AbstractionEntity
{
    public function __construct(
        protected ?Uuid   $id = null,
        protected ?string $email = null,
    )
    {
    }

    public static function fromState(array $data): self
    {
        return new self(
            empty($data['user_external_id']) ? null : new Uuid($data['user_external_id']),
            $data['email_seller'] ?? null,
        );
    }

    public function getUserExternalId(): ?Uuid
    {
        return $this->user_external_id;
    }
}
