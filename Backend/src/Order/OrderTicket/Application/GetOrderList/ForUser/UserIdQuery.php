<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetOrderList\ForUser;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

class UserIdQuery implements Query
{
    public function __construct(
        private Uuid $userId
    ) {
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }
}
