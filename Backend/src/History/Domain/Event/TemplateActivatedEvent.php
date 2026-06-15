<?php

declare(strict_types=1);

namespace Tickets\History\Domain\Event;

use Tickets\History\Domain\HistoryEventInterface;

/** Шаблон включён/выключен. Выключение (active=false) = откат рендера на blade-fallback. */
final class TemplateActivatedEvent implements HistoryEventInterface
{
    public function __construct(
        private bool $active,
    ) {
    }

    public function getAggregateType(): string
    {
        return 'template';
    }

    public function getEventName(): string
    {
        return 'template_activated';
    }

    public function getPayload(): array
    {
        return ['active' => $this->active];
    }
}
