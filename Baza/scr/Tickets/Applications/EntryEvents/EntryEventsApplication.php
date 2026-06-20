<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\EntryEvents;

use Baza\Changes\Applications\AddTicketsInReport\AddTicketsInReport;
use Baza\Tickets\Applications\Enter\EnterTicket;
use Baza\Tickets\Repositories\BlacklistRepositoryInterface;
use Baza\Tickets\Repositories\EntryEventRepositoryInterface;
use Carbon\Carbon;
use Throwable;

/**
 * Дренаж офлайн-намерений впуска в append-only журнал (Ф5, PR-8) — гейт мульти-устройства.
 *
 * Телефон присылает накопленные офлайн намерения; на каждое:
 *   - идемпотентность по client_op_id (повторный дренаж не дублирует);
 *   - отозванный (blacklist, B6) → не пускаем;
 *   - «первый впуск побеждает»: если билет уже впущен (журнал/таблица) → is_duplicate, без счётчика;
 *   - иначе — реальный впуск (тот же EnterTicket + счётчик смены, что онлайн /api/enter).
 * Журнал append-only: запись добавляется всегда (для аудита и пересчёта), счётчик растёт
 * только на первом успешном впуске.
 *
 * FOLLOW-UP к боевому мульти-устройству (гейт активации офлайн-впуска на реальном фесте):
 *  1) TOCTOU в EnterTicket::skip() — read-check-write по date_change БЕЗ lockForUpdate.
 *     При ПАРАЛЛЕЛЬНЫХ дренажах/онлайн-впуске на один билет возможен задвоенный счётчик.
 *     В одном HTTP-дренаже гонки нет (события идут последовательно). До мульти-устройства —
 *     добавить lockForUpdate/уникальный страж в skip().
 *  2) Онлайн /api/enter НЕ пишет в этот журнал — пересчёт «из журнала» = журнал ∪ date_change
 *     (журнал = офлайн-дренаж, date_change = авторитет онлайн-впуска). Либо писать онлайн в журнал.
 */
class EntryEventsApplication
{
    public function __construct(
        private readonly EntryEventRepositoryInterface $journal,
        private readonly BlacklistRepositoryInterface $blacklist,
        private readonly EnterTicket $enterTicket,
        private readonly AddTicketsInReport $addTicketsInReport,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $events
     * @return array<int, array{client_op_id: string, status: string}>
     */
    public function ingestBatch(array $events, int $changeId): array
    {
        $results = [];
        foreach ($events as $e) {
            $results[] = $this->ingestOne(is_array($e) ? $e : [], $changeId);
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $e
     * @return array{client_op_id: string, status: string}
     */
    private function ingestOne(array $e, int $changeId): array
    {
        $clientOpId = (string) ($e['client_op_id'] ?? '');
        if ($clientOpId === '') {
            return ['client_op_id' => '', 'status' => 'error'];
        }

        // Идемпотентность: повторный дренаж того же намерения — no-op.
        if ($this->journal->existsByClientOp($clientOpId)) {
            return ['client_op_id' => $clientOpId, 'status' => 'already'];
        }

        $type = (string) ($e['type'] ?? '');
        $kilter = isset($e['kilter']) && is_numeric($e['kilter']) ? (int) $e['kilter'] : null;
        $uuid = isset($e['ticket_uuid']) && is_string($e['ticket_uuid']) && $e['ticket_uuid'] !== ''
            ? $e['ticket_uuid'] : null;

        $base = [
            'client_op_id' => $clientOpId,
            'type' => $type,
            'kilter' => $kilter,
            'ticket_uuid' => $uuid,
            'device_id' => isset($e['device_id']) ? (string) $e['device_id'] : null,
            'change_id' => $changeId,
            'entered_at' => $this->parseDate($e['entered_at'] ?? null),
            'festival_id' => isset($e['festival_id']) ? (string) $e['festival_id'] : null,
            'nonce' => isset($e['nonce']) ? (string) $e['nonce'] : null,
        ];

        // B6: отозванный билет — не пускаем (в журнал пишем для аудита как не-засчитанный).
        if ($this->blacklist->isRevoked($uuid, $kilter)) {
            $this->journal->append($base + ['is_duplicate' => true]);

            return ['client_op_id' => $clientOpId, 'status' => 'revoked'];
        }

        // Без номера впустить нельзя (skip требует kilter) — консьюмим как ошибку.
        if ($kilter === null) {
            $this->journal->append($base + ['is_duplicate' => true]);

            return ['client_op_id' => $clientOpId, 'status' => 'error'];
        }

        // «Первый впуск побеждает».
        $duplicate = $this->journal->firstEntryExists($type, $kilter, $uuid);
        if (! $duplicate) {
            try {
                $this->enterTicket->skip($type, $kilter, $changeId);
                $this->addTicketsInReport->increment($changeId, $type);
            } catch (Throwable) {
                // Уже впущен на сервере (date_change) или билет не найден → дубликат.
                $duplicate = true;
            }
        }

        $this->journal->append($base + ['is_duplicate' => $duplicate]);

        return ['client_op_id' => $clientOpId, 'status' => $duplicate ? 'duplicate' : 'entered'];
    }

    private function parseDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            return Carbon::parse($value)->toDateTimeString();
        } catch (Throwable) {
            return null;
        }
    }
}
