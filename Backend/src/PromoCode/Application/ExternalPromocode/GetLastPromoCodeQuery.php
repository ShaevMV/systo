<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Application\ExternalPromocode;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

final class GetLastPromoCodeQuery implements Query
{
    public function __construct(
        protected Uuid $orderId
    ){}

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }

}
