<?php

declare(strict_types = 1);

namespace Tickets\Order\OrderTicket\Repositories;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\CommentDto;
use Tickets\Order\OrderTicket\ValueObject\CommentForOrder;

interface CommentRepositoryInterface
{
    public function addComment(CommentDto $commentDto): bool;

    /**
     * Весь тред комментариев заказа в хронологическом порядке (created_at ASC).
     *
     * @return CommentForOrder[]
     */
    public function listByOrder(Uuid $orderId): array;

    /** Одна запись треда по id (null если нет). */
    public function findById(Uuid $id): ?CommentForOrder;
}
