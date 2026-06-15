<?php

declare(strict_types=1);

namespace Tickets\History\Domain\Event;

use Tickets\History\Domain\HistoryEventInterface;

/** Откат тела шаблона к версии (создаёт новую версию-«откат», история append-only). */
final class TemplateRolledBackEvent implements HistoryEventInterface
{
    public function __construct(
        private string $toVersionId,
        private ?string $toDate = null,
    ) {
    }

    public function getAggregateType(): string
    {
        return 'template';
    }

    public function getEventName(): string
    {
        return 'template_rolled_back';
    }

    public function getPayload(): array
    {
        return array_filter([
            'to_version_id' => $this->toVersionId,
            'to_date' => $this->toDate,
        ], static fn ($value) => $value !== null);
    }
}
