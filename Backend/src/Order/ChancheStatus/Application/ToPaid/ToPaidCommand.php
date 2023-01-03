<?php

namespace Tickets\Order\ChancheStatus\Application\ToPaid;

use Tickets\Shared\Domain\Bus\Command\Command;
use Tickets\Shared\Domain\ValueObject\Uuid;

class ToPaidCommand implements Command
{
    public function __construct(
        private Uuid $orderId
    ) {
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }
}
