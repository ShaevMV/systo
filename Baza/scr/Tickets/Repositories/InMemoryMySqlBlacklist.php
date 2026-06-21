<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use App\Models\BlacklistModel;
use Baza\Tickets\Responses\BlacklistItemResponse;
use Baza\Tickets\Responses\BlacklistPageResponse;

/**
 * Чёрный список отозванных билетов (Ф5, PR-6, B6). БД только здесь.
 */
class InMemoryMySqlBlacklist implements BlacklistRepositoryInterface
{
    private const UUID_FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';

    private const PAGE_DEFAULT_LIMIT = 1000;

    private const PAGE_MAX_LIMIT = 5000;

    public function revoke(?string $ticketUuid, ?int $kilter, ?string $festivalId, ?string $reason): bool
    {
        $uuid = ($ticketUuid !== null && $ticketUuid !== '') ? $ticketUuid : null;
        $reason = ($reason !== null && trim($reason) !== '') ? trim($reason) : null;

        if ($uuid !== null) {
            BlacklistModel::query()->updateOrCreate(
                ['ticket_uuid' => $uuid],
                ['kilter' => $kilter, 'festival_id' => $festivalId, 'reason' => $reason],
            );

            return true;
        }

        if ($kilter !== null) {
            BlacklistModel::query()->updateOrCreate(
                ['kilter' => $kilter, 'festival_id' => $festivalId],
                ['reason' => $reason],
            );

            return true;
        }

        return false;
    }

    public function isRevoked(?string $ticketUuid, ?int $kilter): bool
    {
        $uuid = ($ticketUuid !== null && $ticketUuid !== '') ? $ticketUuid : null;
        if ($uuid === null && $kilter === null) {
            return false;
        }

        return BlacklistModel::query()
            ->when($uuid !== null, fn ($q) => $q->where('ticket_uuid', $uuid))
            ->when($uuid === null && $kilter !== null, fn ($q) => $q->where('kilter', $kilter))
            ->exists();
    }

    public function page(?string $festivalId, ?string $since, int $afterId, int $limit): BlacklistPageResponse
    {
        $festival = ($festivalId !== null && $festivalId !== '') ? $festivalId : self::UUID_FESTIVAL;
        $limit = $limit > 0 ? min($limit, self::PAGE_MAX_LIMIT) : self::PAGE_DEFAULT_LIMIT;
        $afterId = max(0, $afterId);

        $query = BlacklistModel::query()
            ->where('festival_id', $festival)
            ->where('id', '>', $afterId);

        if ($since !== null && $since !== '') {
            $query->where('updated_at', '>=', $since);
        }

        $rows = $query->orderBy('id')
            ->limit($limit + 1)
            ->get(['id', 'ticket_uuid', 'kilter', 'reason', 'festival_id', 'updated_at']);

        $hasMore = $rows->count() > $limit;
        $page = $rows->take($limit);

        $items = $page
            ->map(fn (BlacklistModel $model) => BlacklistItemResponse::fromState($model->toArray()))
            ->all();

        $nextAfterId = $page->isNotEmpty() ? (int) $page->last()->id : $afterId;

        return new BlacklistPageResponse($items, $nextAfterId, $hasMore);
    }
}
