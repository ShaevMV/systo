<?php

declare(strict_types=1);

namespace Tickets\Integration\Qr\Repositories;

use App\Models\Integration\ProcessedMessageModel;
use Tickets\Integration\Qr\Dto\ProcessedMessageDto;

final class InMemoryMySqlProcessedMessageRepository implements ProcessedMessageRepositoryInterface
{
    public function __construct(
        private ProcessedMessageModel $model,
    ) {
    }

    public function isProcessed(string $idempotencyKey): bool
    {
        return $this->model::where('idempotency_key', $idempotencyKey)->exists();
    }

    public function markProcessed(ProcessedMessageDto $dto): void
    {
        $this->model::create([
            'idempotency_key' => $dto->idempotencyKey,
            'event_type' => $dto->eventType,
            'source' => $dto->source,
            'trace_id' => $dto->traceId,
            // processed_at не задаём — БД ставит DEFAULT CURRENT_TIMESTAMP (единый формат данных)
        ]);
    }
}
