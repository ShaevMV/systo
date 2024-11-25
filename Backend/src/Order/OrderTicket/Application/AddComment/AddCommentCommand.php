<?php

namespace Tickets\Order\OrderTicket\Application\AddComment;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;

class AddCommentCommand implements Command
{
    public function __construct(
        private Uuid $orderId,
        private Uuid $userId,
        private string $message,
    ) {
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }
}
