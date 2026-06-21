<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\ListComments;

use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\Order\OrderTicket\Responses\CommentResponse;
use Tickets\Order\OrderTicket\Responses\CommentThreadResponse;
use Tickets\Order\OrderTicket\ValueObject\CommentForOrder;

/**
 * Тонкий слой чтения треда комментариев заказа (как GetOrderHistory).
 */
final class ListOrderComments
{
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(
        ListOrderCommentsQueryHandler $handler,
        GetOrderCommentQueryHandler $itemHandler,
    ) {
        $this->queryBus = new InMemorySymfonyQueryBus([
            ListOrderCommentsQuery::class => $handler,
            GetOrderCommentQuery::class   => $itemHandler,
        ]);
    }

    /**
     * @return CommentForOrder[]
     */
    public function getByOrderId(Uuid $orderId): array
    {
        /** @var CommentThreadResponse|null $result */
        if ($result = $this->queryBus->ask(new ListOrderCommentsQuery($orderId))) {
            return $result->list;
        }

        return [];
    }

    public function getById(Uuid $id): ?CommentForOrder
    {
        /** @var CommentResponse|null $result */
        if ($result = $this->queryBus->ask(new GetOrderCommentQuery($id))) {
            return $result->comment;
        }

        return null;
    }
}
