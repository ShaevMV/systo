<?php

declare(strict_types=1);

namespace Tickets\Festival\Domain;

use Shared\Domain\Aggregate\AggregateRoot;
use Shared\Domain\ValueObject\Uuid;
use Tickets\History\Domain\Event\FestivalCreatedEvent;
use Tickets\History\Domain\Event\FestivalDeletedEvent;
use Tickets\History\Domain\Event\FestivalEditedEvent;
use Tickets\History\Trait\HasHistory;

/**
 * Агрегат фестиваля.
 *
 * Поднят из пассивной сущности (DTO + репозиторий) в AggregateRoot ради записи ИСТОРИИ
 * изменений — по образцу Template/OrderTicket. Каждое действие пишет recordHistory(...),
 * а FestivalApplication вытаскивает pullHistoryEvents() и сохраняет в domain_history
 * (aggregate_type='festival', actor_type=user).
 *
 * Состояние и персист — в репозитории (Dependency Rule, БД только там). Здесь — идентичность
 * агрегата + журнал доменных действий (create/edit/delete).
 */
final class Festival extends AggregateRoot
{
    use HasHistory;

    private function __construct(
        private readonly Uuid $id,
    ) {
    }

    /** Новый фестиваль создан. */
    public static function created(Uuid $id, string $name, int $year, bool $active): self
    {
        $self = new self($id);
        $self->recordHistory(new FestivalCreatedEvent($name, $year, $active));

        return $self;
    }

    /** Существующий фестиваль — точка входа для действий над ним. */
    public static function existing(Uuid $id): self
    {
        return new self($id);
    }

    /**
     * Изменены поля. $changed пуст → история НЕ пишется
     * (как Template::edited / OrderTicket::toChangeTicket при отсутствии изменений).
     *
     * @param array<int, string> $changed
     */
    public function edited(array $changed): void
    {
        if ($changed === []) {
            return;
        }

        $this->recordHistory(new FestivalEditedEvent($changed));
    }

    /** Фестиваль удалён (soft delete). */
    public function deleted(): void
    {
        $this->recordHistory(new FestivalDeletedEvent());
    }

    public function id(): Uuid
    {
        return $this->id;
    }
}
