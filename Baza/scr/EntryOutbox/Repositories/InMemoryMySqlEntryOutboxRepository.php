<?php

declare(strict_types=1);

namespace Baza\EntryOutbox\Repositories;

use Baza\EntryOutbox\Dto\EntryOutboxDto;
use Baza\Shared\Services\DefineService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Shared\Domain\ValueObject\Uuid;

/**
 * Запись/чтение буфера вебхука входа (Ф4). Через query-builder (как InMemoryMySqlSyncRepository).
 */
class InMemoryMySqlEntryOutboxRepository implements EntryOutboxRepositoryInterface
{
    private const TABLE = 'baza_entry_outbox';

    /** Тип впуска (DefineService) → [таблица билета, колонка-источник org-uuid, ключ поиска]. */
    private const RESOLVE_MAP = [
        DefineService::ELECTRON_TICKET => ['el_tickets', 'uuid', 'kilter'],
        DefineService::SPISOK_TICKET => ['spisok_tickets', 'ticket_uuid', 'kilter'],
        DefineService::LIVE_TICKET => ['live_tickets', 'el_ticket_id', 'kilter'],
        DefineService::AUTO_TICKET => ['auto', 'order_id', 'id'],
    ];

    /** Тип впуска → target в outbox. */
    private const TARGET_MAP = [
        DefineService::ELECTRON_TICKET => 'el_tickets',
        DefineService::SPISOK_TICKET => 'spisok_tickets',
        DefineService::LIVE_TICKET => 'live_tickets',
        DefineService::AUTO_TICKET => 'auto',
    ];

    public function enqueue(
        string $target,
        ?string $ticketUuid,
        ?int $kilter,
        ?int $changeId,
        string $enteredAt,
        ?string $wristbandQr = null,
    ): bool {
        // Без org-идентификатора вебхук бесполезен (org не свяжет) — не пишем.
        if ($ticketUuid === null || $ticketUuid === '') {
            return false;
        }

        $now = Carbon::now()->format('Y-m-d H:i:s');

        // Идемпотентно по (target, ticket_uuid): билет входит один раз (skip защищает от повтора).
        $exists = DB::table(self::TABLE)
            ->where('target', $target)
            ->where('ticket_uuid', $ticketUuid)
            ->exists();

        if ($exists) {
            // Повтор: обновляем справочные поля, НЕ трогая status/attempts (могли уже уйти на org).
            DB::table(self::TABLE)
                ->where('target', $target)
                ->where('ticket_uuid', $ticketUuid)
                ->update([
                    'kilter' => $kilter,
                    'change_id' => $changeId,
                    'entered_at' => $enteredAt,
                    'wristband_qr' => $wristbandQr,
                    'updated_at' => $now,
                ]);

            return true;
        }

        return DB::table(self::TABLE)->insert([
            'id' => Uuid::random()->value(),
            'target' => $target,
            'ticket_uuid' => $ticketUuid,
            'kilter' => $kilter,
            'change_id' => $changeId,
            'entered_at' => $enteredAt,
            'wristband_qr' => $wristbandQr,
            'status' => 'pending',
            'attempts' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function resolveTicketUuid(string $type, int $id): ?string
    {
        $map = self::RESOLVE_MAP[$type] ?? null;
        if ($map === null) {
            return null;
        }

        [$table, $column, $key] = $map;

        $value = DB::table($table)->where($key, $id)->value($column);

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    public function targetForType(string $type): ?string
    {
        return self::TARGET_MAP[$type] ?? null;
    }

    public function pending(int $limit, int $maxAttempts): array
    {
        return DB::table(self::TABLE)
            ->whereIn('status', ['pending', 'failed'])
            ->where('attempts', '<', $maxAttempts)
            ->orderBy('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => EntryOutboxDto::fromState((array) $row))
            ->all();
    }

    public function markSending(string $id): bool
    {
        return DB::table(self::TABLE)->where('id', $id)->update([
            'status' => 'sending',
            'attempts' => DB::raw('attempts + 1'),
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]) >= 0;
    }

    public function markSent(string $id): bool
    {
        return DB::table(self::TABLE)->where('id', $id)->update([
            'status' => 'sent',
            'error' => null,
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]) >= 0;
    }

    public function markFailed(string $id, string $error): bool
    {
        return DB::table(self::TABLE)->where('id', $id)->update([
            'status' => 'failed',
            'error' => mb_substr($error, 0, 1000),
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]) >= 0;
    }
}
