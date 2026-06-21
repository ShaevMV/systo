<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Responses;

use Shared\Domain\Bus\Query\Response;
use Tickets\Order\OrderTicket\ValueObject\CommentForOrder;

/**
 * Одна запись треда комментариев заказа.
 */
final class CommentResponse implements Response
{
    public function __construct(
        public CommentForOrder $comment,
    ) {
    }
}
