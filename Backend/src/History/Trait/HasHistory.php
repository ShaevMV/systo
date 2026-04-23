<?php

declare(strict_types=1);

namespace Tickets\History\Trait;

use Tickets\History\Domain\HistoryEventInterface;

/**
 * Trait для агрегатов, поддерживающих запись истории изменений.
 * Используется параллельно с AggregateRoot::record() — не заменяет его.
 *
 * Подключение: use HasHistory; в классе агрегата.
 */
trait HasHistory
{
    private array $historyEvents = [];

    protected function recordHistory(HistoryEventInterface $event): void
    {
        $this->historyEvents[] = $event;
    }

    /** Возвращает накопленные события истории и очищает буфер. */
    public function pullHistoryEvents(): array
    {
        $events             = $this->historyEvents;
        $this->historyEvents = [];
        return $events;
    }
}
