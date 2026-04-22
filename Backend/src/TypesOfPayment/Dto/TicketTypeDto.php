<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Dto;

use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

class TicketTypeDto extends AbstractionEntity
{
    public function __construct(
        protected ?Uuid   $id = null,
        protected ?string $name = null,
    )
    {
    }

    public static function fromState(array $data): self
    {
        return new self(
            empty($data['ticket_type_id']) ? null : new Uuid($data['ticket_type_id']),
            $data['ticket_type_name'] ?? null,
        );
    }

    public function getTicketTypeId(): ?Uuid
    {
        return $this->id;
    }
}
