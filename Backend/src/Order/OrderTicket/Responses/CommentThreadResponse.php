<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Responses;

use Shared\Domain\Bus\Query\Response;
use Tickets\Order\OrderTicket\ValueObject\CommentForOrder;

/**
 * Тред комментариев заказа (хронологический порядок).
 */
final class CommentThreadResponse implements Response
{
    /**
     * @param CommentForOrder[] $list
     */
    public function __construct(
        public array $list,
    ) {
    }
}
