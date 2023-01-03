<?php

namespace Tickets\Order\InfoForOrder\Application\SearchPromoCode;

use Tickets\Shared\Domain\Bus\Query\Query;

final class PromoCodeQuery implements Query
{
    public function __construct(
        private string $name
    ){
    }

    public function getName(): string
    {
        return $this->name;
    }
}
