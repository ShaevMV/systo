<?php

namespace Tickets\PromoCode\Application\SearchPromoCode;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

final class PromoCodeQuery implements Query
{
    public function __construct(
        private string $name,
        private Uuid $ticketsTypeId,
    ){
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTicketsTypeId(): Uuid
    {
        return $this->ticketsTypeId;
    }
}
