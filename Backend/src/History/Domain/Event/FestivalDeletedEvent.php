<?php

declare(strict_types=1);

namespace Tickets\History\Domain\Event;

use Tickets\History\Domain\HistoryEventInterface;

/** Фестиваль удалён (admin, soft delete). */
final class FestivalDeletedEvent implements HistoryEventInterface
{
    public function getAggregateType(): string
    {
        return 'festival';
    }

    public function getEventName(): string
    {
        return 'festival_deleted';
    }

    public function getPayload(): array
    {
        return [];
    }
}
