<?php

declare(strict_types=1);

namespace Tickets\History\Domain\Event;

use Tickets\History\Domain\HistoryEventInterface;

final class OrderStatusChangedEvent implements HistoryEventInterface
{
    public function __construct(
        private string  $fromStatus,
        private string  $toStatus,
        private ?string $comment = null,
    ) {
    }

    public function getAggregateType(): string
    {
        return 'order';
    }

    public function getEventName(): string
    {
        return 'status_changed';
    }

    public function getPayload(): array
    {
        return array_filter([
            'from'    => $this->fromStatus,
            'to'      => $this->toStatus,
            'comment' => $this->comment,
        ], fn($v) => $v !== null);
    }
}
