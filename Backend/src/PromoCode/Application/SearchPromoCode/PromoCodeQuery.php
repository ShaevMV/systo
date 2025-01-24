<?php

namespace Tickets\PromoCode\Application\SearchPromoCode;

use Shared\Domain\Bus\Query\Query;

final class PromoCodeQuery implements Query
{
    public function __construct(
        private string $name,
        private string $ticketsTypeId,
    ){
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTicketsTypeId(): string
    {
        return $this->ticketsTypeId;
    }
}
