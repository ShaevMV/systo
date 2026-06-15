<?php

declare(strict_types=1);

namespace Tickets\Template\Domain;

use Shared\Domain\Aggregate\AggregateRoot;
use Shared\Domain\ValueObject\Uuid;
use Tickets\History\Domain\Event\TemplateActivatedEvent;
use Tickets\History\Domain\Event\TemplateCreatedEvent;
use Tickets\History\Domain\Event\TemplateEditedEvent;
use Tickets\History\Domain\Event\TemplatePublishedEvent;
use Tickets\History\Domain\Event\TemplateRolledBackEvent;
use Tickets\History\Trait\HasHistory;

/**
 * Агрегат шаблона письма/PDF.
 *
 * Поднят из пассивной сущности (DTO+репозиторий) в AggregateRoot ради записи ИСТОРИИ
 * изменений — по образцу OrderTicket. Каждое действие пишет recordHistory(...), а Application
 * вытаскивает pullHistoryEvents() и сохраняет в domain_history (aggregate_type='template').
 *
 * Состояние и персист — в репозитории (Dependency Rule, БД только там). Здесь — идентичность
 * агрегата + журнал доменных действий (create/edit/activate/publish/rollback).
 *
 * Версии тела (template_versions) и история (domain_history) — РАЗНОЕ: версии = снапшоты тела
 * для отката/diff, история = аудит «кто/что/когда».
 */
final class Template extends AggregateRoot
{
    use HasHistory;

    private function __construct(
        private readonly Uuid $id,
    ) {
    }

    /** Новый шаблон создан. */
    public static function created(Uuid $id, string $slug, string $kind, string $title): self
    {
        $self = new self($id);
        $self->recordHistory(new TemplateCreatedEvent($slug, $kind, $title));

        return $self;
    }

    /** Существующий шаблон — точка входа для действий над ним. */
    public static function existing(Uuid $id): self
    {
        return new self($id);
    }

    /**
     * Изменены метаданные/тело. $changed пуст → история НЕ пишется
     * (как OrderTicket::toChangeTicket при отсутствии реальных изменений).
     *
     * @param array<int, string> $changed
     */
    public function edited(array $changed): void
    {
        if ($changed === []) {
            return;
        }

        $this->recordHistory(new TemplateEditedEvent($changed));
    }

    public function activated(bool $active): void
    {
        $this->recordHistory(new TemplateActivatedEvent($active));
    }

    public function published(?string $comment): void
    {
        $this->recordHistory(new TemplatePublishedEvent($comment));
    }

    public function rolledBack(string $toVersionId, ?string $toDate): void
    {
        $this->recordHistory(new TemplateRolledBackEvent($toVersionId, $toDate));
    }

    public function id(): Uuid
    {
        return $this->id;
    }
}
