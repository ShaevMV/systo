<?php

declare(strict_types=1);

namespace Tickets\History\Domain\Event;

use Tickets\History\Domain\HistoryEventInterface;

/** Изменены метаданные/тело шаблона. payload.changed — список изменённых полей (без самих тел). */
final class TemplateEditedEvent implements HistoryEventInterface
{
    /** @param array<int, string> $changed */
    public function __construct(
        private array $changed,
    ) {
    }

    public function getAggregateType(): string
    {
        return 'template';
    }

    public function getEventName(): string
    {
        return 'template_edited';
    }

    public function getPayload(): array
    {
        return ['changed' => $this->changed];
    }
}
