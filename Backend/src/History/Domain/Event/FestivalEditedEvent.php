<?php

declare(strict_types=1);

namespace Tickets\History\Domain\Event;

use Tickets\History\Domain\HistoryEventInterface;

/** Фестиваль отредактирован (admin). Payload — список изменившихся полей. */
final class FestivalEditedEvent implements HistoryEventInterface
{
    /** @param array<int, string> $changed */
    public function __construct(
        private array $changed,
    ) {
    }

    public function getAggregateType(): string
    {
        return 'festival';
    }

    public function getEventName(): string
    {
        return 'festival_edited';
    }

    public function getPayload(): array
    {
        return [
            'changed' => $this->changed,
        ];
    }
}
