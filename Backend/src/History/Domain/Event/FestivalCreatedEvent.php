<?php

declare(strict_types=1);

namespace Tickets\History\Domain\Event;

use Tickets\History\Domain\HistoryEventInterface;

/** Фестиваль создан (admin). */
final class FestivalCreatedEvent implements HistoryEventInterface
{
    public function __construct(
        private string $name,
        private int $year,
        private bool $active,
    ) {
    }

    public function getAggregateType(): string
    {
        return 'festival';
    }

    public function getEventName(): string
    {
        return 'festival_created';
    }

    public function getPayload(): array
    {
        return [
            'name' => $this->name,
            'year' => $this->year,
            'active' => $this->active,
        ];
    }
}
