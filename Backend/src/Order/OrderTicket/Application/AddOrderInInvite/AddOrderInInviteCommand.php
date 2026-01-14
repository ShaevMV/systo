<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\AddOrderInInvite;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;

class AddOrderInInviteCommand implements Command
{
    public function __construct(
        private Uuid $id,
        private Uuid $orderId,
    )
    {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }
}
