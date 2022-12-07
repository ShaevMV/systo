<?php

namespace Tickets\Ordering\OrderTicket\Application\AddComment;

use Tickets\Shared\Domain\Bus\Command\Command;
use Tickets\Shared\Domain\ValueObject\Uuid;

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
