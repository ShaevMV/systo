<?php

namespace Tickets\Order\OrderTicket\Application\ChanceStatus;

use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

class ChanceStatusCommand
{
    public function __construct(
        private Status $nextStatus,
        private Uuid $orderId,
    ){
    }

    public function getNextStatus(): Status
    {
        return $this->nextStatus;
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }
}
