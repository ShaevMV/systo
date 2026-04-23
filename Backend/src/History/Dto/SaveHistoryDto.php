<?php

declare(strict_types=1);

namespace Tickets\History\Dto;

use Tickets\History\Domain\ActorType;
use Tickets\History\Domain\HistoryEventInterface;

final class SaveHistoryDto
{
    public function __construct(
        public readonly string  $aggregateId,
        public readonly HistoryEventInterface $event,
        public readonly ?string $actorId,
        public readonly string  $actorType = ActorType::USER,
    ) {
    }

    public function toArray(): array
    {
        return [
            'aggregate_id'   => $this->aggregateId,
            'aggregate_type' => $this->event->getAggregateType(),
            'event_name'     => $this->event->getEventName(),
            'payload'        => $this->event->getPayload(),
            'actor_id'       => $this->actorId,
            'actor_type'     => $this->actorType,
        ];
    }
}
