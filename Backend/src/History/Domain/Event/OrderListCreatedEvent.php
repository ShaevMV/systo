<?php

declare(strict_types=1);

namespace Tickets\History\Domain\Event;

use Tickets\History\Domain\HistoryEventInterface;

/**
 * Событие истории для создания заказа-списка.
 * В отличие от OrderCreatedEvent НЕ содержит ticket_type/price (их нет у списков),
 * а содержит location_id, location_name, project.
 */
final class OrderListCreatedEvent implements HistoryEventInterface
{
    public function __construct(
        private string  $locationId,
        private ?string $locationName,
        private ?string $project,
        private int     $kilter,
    ) {
    }

    public function getAggregateType(): string
    {
        return 'order';
    }

    public function getEventName(): string
    {
        return 'order_list_created';
    }

    public function getPayload(): array
    {
        return [
            'location_id'   => $this->locationId,
            'location_name' => $this->locationName,
            'project'       => $this->project,
            'kilter'        => $this->kilter,
        ];
    }
}
