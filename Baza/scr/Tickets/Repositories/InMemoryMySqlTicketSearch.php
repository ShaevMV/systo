<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use App\Models\TicketSearchModel;
use Baza\Tickets\Responses\SnapshotItemResponse;
use Baza\Tickets\Responses\SnapshotPageResponse;
use Baza\Tickets\Responses\TicketSearchResponse;
use Illuminate\Database\Eloquent\Builder;

/**
 * Поисковый индекс билетов ticket_search. Наполнение из ingest + поиск без QR. БД только здесь.
 */
class InMemoryMySqlTicketSearch implements TicketSearchRepositoryInterface
{
    /** Текущий фестиваль (как в остальных репозиториях поиска; кандидат на env, BAZA.md §9). */
    private const UUID_FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';

    /** Сколько результатов отдавать (защита от широких запросов на слабом устройстве). */
    private const LIMIT = 100;

    /** Размер порции снимка по умолчанию и потолок (память телефона). */
    private const SNAPSHOT_DEFAULT_LIMIT = 500;

    private const SNAPSHOT_MAX_LIMIT = 1000;

    /** Колонки проекции, по которым идёт текстовый поиск. */
    private const SEARCH_COLUMNS = [
        'fio', 'phone', 'telegram', 'email', 'city',
        'car_number', 'child_name', 'parent_phone', 'external_order_no',
    ];

    public function index(array $row): bool
    {
        $uuid = (string) ($row['ticket_uuid'] ?? '');
        if ($uuid === '') {
            return false;
        }

        TicketSearchModel::query()->updateOrCreate(
            ['ticket_uuid' => $uuid],
            [
                'festival_id' => $row['festival_id'] ?? null,
                'type' => (string) ($row['type'] ?? ''),
                'kilter' => isset($row['kilter']) ? (int) $row['kilter'] : null,
                'fio' => $this->trimOrNull($row['fio'] ?? null),
                'phone' => $this->trimOrNull($row['phone'] ?? null),
                'telegram' => $this->trimOrNull($row['telegram'] ?? null),
                'email' => $this->trimOrNull($row['email'] ?? null),
                'city' => $this->trimOrNull($row['city'] ?? null),
                'car_number' => $this->trimOrNull($row['car_number'] ?? null),
                'child_name' => $this->trimOrNull($row['child_name'] ?? null),
                'parent_phone' => $this->trimOrNull($row['parent_phone'] ?? null),
                'external_order_no' => $this->trimOrNull($row['external_order_no'] ?? null),
                'type_ticket' => $this->trimOrNull($row['type_ticket'] ?? null),
                'payload' => is_array($row['payload'] ?? null) ? $row['payload'] : null,
            ],
        );

        return true;
    }

    public function snapshot(?string $festivalId, ?string $since, int $afterId, int $limit): SnapshotPageResponse
    {
        $festival = ($festivalId !== null && $festivalId !== '') ? $festivalId : self::UUID_FESTIVAL;
        $limit = $limit > 0 ? min($limit, self::SNAPSHOT_MAX_LIMIT) : self::SNAPSHOT_DEFAULT_LIMIT;
        $afterId = max(0, $afterId);

        $query = TicketSearchModel::query()
            ->where('festival_id', $festival)
            ->where('id', '>', $afterId);

        // Дельта: только изменённые с момента since (updated_at >=). null → полный снимок.
        if ($since !== null && $since !== '') {
            $query->where('updated_at', '>=', $since);
        }

        // Берём на 1 строку больше лимита — чтобы узнать has_more без отдельного count().
        $rows = $query->orderBy('id')
            ->limit($limit + 1)
            ->get(['id', 'ticket_uuid', 'kilter', 'type', 'type_ticket', 'fio', 'festival_id', 'updated_at']);

        $hasMore = $rows->count() > $limit;
        $page = $rows->take($limit);

        $items = $page
            ->map(fn (TicketSearchModel $model) => SnapshotItemResponse::fromState($model->toArray()))
            ->all();

        $nextAfterId = $page->isNotEmpty() ? (int) $page->last()->id : $afterId;

        return new SnapshotPageResponse($items, $nextAfterId, $hasMore);
    }

    public function find(string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $like = '%'.$q.'%';

        return TicketSearchModel::query()
            ->where('festival_id', self::UUID_FESTIVAL)
            ->where(function (Builder $query) use ($like, $q): void {
                foreach (self::SEARCH_COLUMNS as $column) {
                    $query->orWhere($column, 'like', $like);
                }
                if (ctype_digit($q)) {
                    $query->orWhere('kilter', (int) $q);
                }
            })
            ->orderBy('id')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (TicketSearchModel $model) => TicketSearchResponse::fromState($model->toArray()))
            ->all();
    }

    private function trimOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return $value === null ? null : (string) $value;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
