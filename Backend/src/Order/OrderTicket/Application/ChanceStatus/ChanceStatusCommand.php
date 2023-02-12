<?php

namespace Tickets\Order\OrderTicket\Application\ChanceStatus;

use Tickets\Shared\Domain\Bus\Command\Command;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

class ChanceStatusCommand implements Command
{
    public function __construct(
        private Uuid $orderId,
        private Status $nextStatus,
        private Uuid $userId,
        private ?string $comment,
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
}
