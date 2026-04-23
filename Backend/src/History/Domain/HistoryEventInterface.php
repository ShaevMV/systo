<?php

declare(strict_types=1);

namespace Tickets\History\Domain;

interface HistoryEventInterface
{
    /** Тип агрегата: 'order' | 'ticket' | ... */
    public function getAggregateType(): string;

    /** Читаемое имя события: 'status_changed' | 'ticket_data_changed' | ... */
    public function getEventName(): string;

    /** Данные снимка: ['from' => ..., 'to' => ..., 'comment' => ...] */
    public function getPayload(): array;
}
