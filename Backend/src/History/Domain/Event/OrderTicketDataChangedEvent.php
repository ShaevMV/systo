<?php

declare(strict_types=1);

namespace Tickets\History\Domain\Event;

use Tickets\History\Domain\HistoryEventInterface;

final class OrderTicketDataChangedEvent implements HistoryEventInterface
{
    /**
     * @param array $changes [{oldName: string, newName: string}]
     */
    public function __construct(
        private array $changes,
    ) {
    }

    public function getAggregateType(): string
    {
        return 'order';
    }

    public function getEventName(): string
    {
        return 'ticket_data_changed';
    }

    public function getPayload(): array
    {
        return ['changes' => $this->changes];
    }
}
