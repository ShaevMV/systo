<?php

namespace Tickets\Order\OrderTicket\Application\ChanceStatus;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;

class ChanceStatusCommand implements Command
{
    public function __construct(
        public Uuid $orderId,
        public Status $nextStatus,
        public Uuid $userId,
        public ?string $comment = null,
        public bool $now = false,
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

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function isNow(): bool
    {
        return $this->now;
    }
}
