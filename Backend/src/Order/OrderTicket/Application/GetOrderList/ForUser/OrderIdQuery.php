<?php

declare(strict_types = 1);

namespace Tickets\Order\OrderTicket\Application\GetOrderList\ForUser;

use Tickets\Shared\Domain\Bus\Query\Query;
use Tickets\Shared\Domain\ValueObject\Uuid;

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
