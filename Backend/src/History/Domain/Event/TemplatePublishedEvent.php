<?php

declare(strict_types=1);

namespace Tickets\History\Domain\Event;

use Tickets\History\Domain\HistoryEventInterface;

/** Опубликовано новое тело шаблона (прод body обновлён + снапшот в template_versions). */
final class TemplatePublishedEvent implements HistoryEventInterface
{
    public function __construct(
        private ?string $comment = null,
    ) {
    }

    public function getAggregateType(): string
    {
        return 'template';
    }

    public function getEventName(): string
    {
        return 'template_published';
    }

    public function getPayload(): array
    {
        return array_filter(
            ['comment' => $this->comment],
            static fn ($value) => $value !== null,
        );
    }
}
