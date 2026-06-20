<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use App\Models\EntryEventModel;

/**
 * Append-only журнал проходов (Ф5, PR-8). БД только здесь.
 */
class InMemoryMySqlEntryEvent implements EntryEventRepositoryInterface
{
    public function existsByClientOp(string $clientOpId): bool
    {
        if ($clientOpId === '') {
            return false;
        }

        return EntryEventModel::query()->where('client_op_id', $clientOpId)->exists();
    }

    public function firstEntryExists(string $type, ?int $kilter, ?string $ticketUuid): bool
    {
        $uuid = ($ticketUuid !== null && $ticketUuid !== '') ? $ticketUuid : null;
        if ($kilter === null && $uuid === null) {
            return false;
        }

        return EntryEventModel::query()
            ->where('is_duplicate', false)
            ->where(function ($q) use ($type, $kilter, $uuid): void {
                if ($kilter !== null) {
                    $q->orWhere(fn ($w) => $w->where('type', $type)->where('kilter', $kilter));
                }
                if ($uuid !== null) {
                    $q->orWhere('ticket_uuid', $uuid);
                }
            })
            ->exists();
    }

    public function append(array $fields): bool
    {
        EntryEventModel::query()->create($fields);

        return true;
    }
}
