<?php

declare(strict_types=1);

namespace Tickets\History\Domain\Event;

use Tickets\History\Domain\HistoryEventInterface;

/** Шаблон письма/PDF создан (admin). */
final class TemplateCreatedEvent implements HistoryEventInterface
{
    public function __construct(
        private string $slug,
        private string $kind,
        private string $title,
    ) {
    }

    public function getAggregateType(): string
    {
        return 'template';
    }

    public function getEventName(): string
    {
        return 'template_created';
    }

    public function getPayload(): array
    {
        return [
            'slug' => $this->slug,
            'kind' => $this->kind,
            'title' => $this->title,
        ];
    }
}
