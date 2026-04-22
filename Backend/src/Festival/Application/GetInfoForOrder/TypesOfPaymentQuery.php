<?php

namespace Tickets\Festival\Application\GetInfoForOrder;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

class TypesOfPaymentQuery implements Query
{
    public function __construct(
        private bool $isForAdmin = false,
        private ?Uuid $ticketTypeId = null,
    )
    {
    }

    public function isForAdmin(): bool
    {
        return $this->isForAdmin;
    }

    public function getTicketTypeId(): ?Uuid
    {
        return $this->ticketTypeId;
    }
}
