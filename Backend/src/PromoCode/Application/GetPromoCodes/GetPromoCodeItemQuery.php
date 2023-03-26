<?php

namespace Tickets\PromoCode\Application\GetPromoCodes;

use Tickets\Shared\Domain\Bus\Query\Query;
use Tickets\Shared\Domain\ValueObject\Uuid;

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
