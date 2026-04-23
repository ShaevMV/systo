<?php

declare(strict_types=1);

namespace Tickets\History\Domain\Event;

use Tickets\History\Domain\HistoryEventInterface;

final class OrderCreatedEvent implements HistoryEventInterface
{
    public function __construct(
        private string $ticketType,
        private float  $price,
        private int    $kilter,
    ) {
    }

    public function getAggregateType(): string
    {
        return 'order';
    }

    public function getEventName(): string
    {
        return 'order_created';
    }

    public function getPayload(): array
    {
        return [
            'ticket_type' => $this->ticketType,
            'price'       => $this->price,
            'kilter'      => $this->kilter,
        ];
    }
}
