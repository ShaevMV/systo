<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\ChangeOrderPrice;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;

class ChangeOrderPriceCommand implements Command
{
    public function __construct(
        public Uuid $orderId,
        public float $price,
        public Uuid $adminId,
    ) {
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getAdminId(): Uuid
    {
        return $this->adminId;
    }
}
