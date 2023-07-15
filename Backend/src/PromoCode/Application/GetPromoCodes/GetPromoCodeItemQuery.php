<?php

namespace Tickets\PromoCode\Application\GetPromoCodes;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

class GetPromoCodeItemQuery implements Query
{
    public function __construct(
        private Uuid $id
    )
    {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}
