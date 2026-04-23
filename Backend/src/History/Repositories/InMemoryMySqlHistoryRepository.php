<?php

declare(strict_types=1);

namespace Tickets\History\Repositories;

use App\Models\History\DomainHistoryModel;
use Throwable;
use Tickets\History\Dto\DomainHistoryDto;
use Tickets\History\Dto\SaveHistoryDto;

final class InMemoryMySqlHistoryRepository implements HistoryRepositoryInterface
{
    public function __construct(
        private DomainHistoryModel $model,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function save(SaveHistoryDto $dto): void
    {
        $this->model::create($dto->toArray());
    }

    /** @return DomainHistoryDto[] */
    public function getByAggregateId(string $aggregateId): array
    {
        return $this->model::where('aggregate_id', $aggregateId)
            ->orderBy('occurred_at', 'asc')
            ->get()
            ->map(fn(DomainHistoryModel $row) => new DomainHistoryDto(
                aggregateId:   $row->aggregate_id,
                aggregateType: $row->aggregate_type,
                eventName:     $row->event_name,
                payload:       is_array($row->payload) ? $row->payload : (json_decode($row->payload, true) ?? []),
                actorId:       $row->actor_id,
                actorType:     $row->actor_type,
                occurredAt:    $row->occurred_at,
            ))
            ->all();
    }
}
