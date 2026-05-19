<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\ChangeOrderPrice;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;
use Tickets\History\Domain\ActorType;

class ChangeOrderPriceCommand implements Command
{
    public function __construct(
        public Uuid    $orderId,
        public float   $price,
        public ?Uuid   $adminId   = null,
        public string  $actorType = ActorType::USER,
        public ?string $reason    = null,
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

    public function getAdminId(): ?Uuid
    {
        return $this->adminId;
    }

    public function getActorType(): string
    {
        return $this->actorType;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }
}
