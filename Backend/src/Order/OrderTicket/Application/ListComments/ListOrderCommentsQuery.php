<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\ListComments;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

final class ListOrderCommentsQuery implements Query
{
    public function __construct(
        public readonly Uuid $orderId,
    ) {
    }
}
