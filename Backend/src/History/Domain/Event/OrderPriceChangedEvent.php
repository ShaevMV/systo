<?php

declare(strict_types=1);

namespace Tickets\History\Domain\Event;

use Tickets\History\Domain\HistoryEventInterface;

final class OrderPriceChangedEvent implements HistoryEventInterface
{
    public function __construct(
        private float   $fromPrice,
        private float   $toPrice,
        private ?string $reason = null,
    ) {
    }

    public function getAggregateType(): string
    {
        return 'order';
    }

    public function getEventName(): string
    {
        return 'price_changed';
    }

    public function getPayload(): array
    {
        return array_filter([
            'from'   => $this->fromPrice,
            'to'     => $this->toPrice,
            'reason' => $this->reason,
        ], fn($v) => $v !== null);
    }
}
