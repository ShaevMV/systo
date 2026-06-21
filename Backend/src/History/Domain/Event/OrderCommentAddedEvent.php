<?php

declare(strict_types=1);

namespace Tickets\History\Domain\Event;

use Tickets\History\Domain\HistoryEventInterface;

/**
 * Событие истории «к заказу добавлен комментарий».
 *
 * Payload БЕЗ ПДн гостей и БЕЗ текста комментария (комментарий — операционная заметка,
 * может содержать ПДн): пишем только источник и длину текста — этого достаточно для таймлайна.
 *
 * aggregate_type = 'order' — как у остальных событий заказа (OrderStatusChangedEvent и пр.).
 */
final class OrderCommentAddedEvent implements HistoryEventInterface
{
    public function __construct(
        private string $source,
        private int $length,
    ) {
    }

    public function getAggregateType(): string
    {
        return 'order';
    }

    public function getEventName(): string
    {
        return 'comment_added';
    }

    public function getPayload(): array
    {
        return [
            'source'    => $this->source,
            'has_text'  => $this->length > 0,
            'length'    => $this->length,
        ];
    }
}
