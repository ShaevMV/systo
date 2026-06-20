<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use App\Models\TicketSearchModel;
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
