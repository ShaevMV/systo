<?php

declare(strict_types=1);

namespace Baza\Ingest\Repositories;

use App\Models\AutoModel;
use App\Models\ElTicketsModel;
use App\Models\LiveTicketModel;
use App\Models\SpisokTicketModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Запись принятых от org билетов в собственные таблицы Baza.
 *
 * Через query-builder (а не Eloquent-fill) — чтобы писать и поля вне $fillable модели
 * (festival_id/city/number), по образцу InMemoryMySqlSyncRepository. Набор полей и
 * семантика upsert — зеркало org-методов setInBaza / setInBazaList / setInBazaLive / setInBazaAuto.
 */
class InMemoryMySqlIngestRepository implements IngestRepositoryInterface
{
    public function upsertElTicket(array $fields): bool
    {
        $uuid = (string) ($fields['uuid'] ?? '');
        if ($uuid === '') {
            return false;
        }

        $now = Carbon::now()->format('Y-m-d H:i:s');
        $exists = DB::table(ElTicketsModel::TABLE)->where('uuid', $uuid)->exists();

        // type_ticket_id/type_ticket в схеме Baza фактически NOT NULL (->default(null) без
        // ->nullable()): на проде non-strict MySQL молча пишет '', strict-режим даёт ошибку.
        // Коалесцируем null → '' для устойчивости (гость без типа билета не должен ронять выдачу).
        $typeTicketId = (string) ($fields['type_ticket_id'] ?? '');
        $typeTicket = (string) ($fields['type_ticket'] ?? '');

        if ($exists) {
            // Обновляем только изменяемые поля (как org: статус/тип/имя/festival).
            return DB::table(ElTicketsModel::TABLE)->where('uuid', $uuid)->update([
                'status' => (string) ($fields['status'] ?? 'paid'),
                'is_need_seedling' => (bool) ($fields['is_need_seedling'] ?? false),
                'type_ticket_id' => $typeTicketId,
                'type_ticket' => $typeTicket,
                'name' => (string) ($fields['name'] ?? ''),
                'festival_id' => $fields['festival_id'] ?? null,
                'updated_at' => $now,
            ]) >= 0;
        }

        return DB::table(ElTicketsModel::TABLE)->insert([
            'kilter' => (int) ($fields['kilter'] ?? 0),
            'uuid' => $uuid,
            'city' => (string) ($fields['city'] ?? ''),
            'name' => (string) ($fields['name'] ?? ''),
            'email' => (string) ($fields['email'] ?? ''),
            'phone' => (string) ($fields['phone'] ?? ''),
            'date_order' => $this->normalizeDate($fields['date_order'] ?? null) ?? $now,
            'status' => (string) ($fields['status'] ?? 'paid'),
            'comment' => $fields['comment'] ?? null,
            'is_need_seedling' => (bool) ($fields['is_need_seedling'] ?? false),
            'type_ticket_id' => $typeTicketId,
            'type_ticket' => $typeTicket,
            'festival_id' => $fields['festival_id'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function upsertSpisokTicket(array $fields): bool
    {
        $ticketUuid = (string) ($fields['ticket_uuid'] ?? '');
        if ($ticketUuid === '') {
            return false;
        }

        $now = Carbon::now()->format('Y-m-d H:i:s');
        $exists = DB::table(SpisokTicketModel::TABLE)->where('ticket_uuid', $ticketUuid)->exists();

        if ($exists) {
            return DB::table(SpisokTicketModel::TABLE)->where('ticket_uuid', $ticketUuid)->update([
                'project' => (string) ($fields['project'] ?? ''),
                'curator' => (string) ($fields['curator'] ?? ''),
                'email' => (string) ($fields['email'] ?? ''),
                'name' => (string) ($fields['name'] ?? ''),
                'comment' => (string) ($fields['comment'] ?? ''),
                'status' => (string) ($fields['status'] ?? 'paid'),
                'festival_id' => $fields['festival_id'] ?? null,
                'updated_at' => $now,
            ]) >= 0;
        }

        return DB::table(SpisokTicketModel::TABLE)->insert([
            'kilter' => (int) ($fields['kilter'] ?? 0),
            'project' => (string) ($fields['project'] ?? ''),
            'curator' => (string) ($fields['curator'] ?? ''),
            'email' => (string) ($fields['email'] ?? ''),
            'name' => (string) ($fields['name'] ?? ''),
            'date_order' => $this->normalizeDate($fields['date_order'] ?? null) ?? $now,
            'comment' => (string) ($fields['comment'] ?? ''),
            'status' => (string) ($fields['status'] ?? 'paid'),
            'ticket_uuid' => $ticketUuid,
            'festival_id' => $fields['festival_id'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function linkLiveTicket(int $kilter, ?string $elTicketId): bool
    {
        $exists = DB::table(LiveTicketModel::TABLE)->where('kilter', $kilter)->exists();
        if (! $exists) {
            // Строки live-билета с таким номером ещё нет — нечего связывать.
            return false;
        }

        DB::table(LiveTicketModel::TABLE)->where('kilter', $kilter)->update([
            'el_ticket_id' => $elTicketId,
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        return true;
    }

    public function upsertAuto(array $fields): bool
    {
        $orderId = (string) ($fields['order_id'] ?? '');
        $auto = (string) ($fields['auto'] ?? '');
        if ($orderId === '' || $auto === '') {
            return false;
        }

        $now = Carbon::now()->format('Y-m-d H:i:s');

        DB::table(AutoModel::TABLE)->updateOrInsert(
            ['order_id' => $orderId, 'auto' => $auto],
            [
                'curator' => (string) ($fields['curator'] ?? ''),
                'project' => (string) ($fields['project'] ?? ''),
                'festival_id' => $fields['festival_id'] ?? null,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        return true;
    }

    /**
     * Нормализует дату из ISO-8601 (org шлёт Carbon→ISO в JSON) к MySQL 'Y-m-d H:i:s'.
     * null/пустую/нечитаемую → null (вызывающий подставит now).
     */
    private function normalizeDate(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }
}
