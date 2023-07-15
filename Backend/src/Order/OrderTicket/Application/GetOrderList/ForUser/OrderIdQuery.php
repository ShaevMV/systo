<?php

declare(strict_types = 1);

namespace Tickets\Order\OrderTicket\Application\GetOrderList\ForUser;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

class OrderIdQuery implements Query
{
    public function __construct(
        private Uuid $orderId,
    ){
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }
}
