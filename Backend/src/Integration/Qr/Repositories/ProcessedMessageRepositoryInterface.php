<?php

declare(strict_types=1);

namespace Tickets\Integration\Qr\Repositories;

use Tickets\Integration\Qr\Dto\ProcessedMessageDto;

/**
 * Дедупликация входящих межсервисных событий (qr → org).
 * Гарантирует at-most-once бизнес-эффект по `idempotency_key`.
 */
interface ProcessedMessageRepositoryInterface
{
    /** Было ли событие с таким idempotency_key уже обработано. */
    public function isProcessed(string $idempotencyKey): bool;

    /** Отметить событие обработанным. UNIQUE на idempotency_key защищает от гонок. */
    public function markProcessed(ProcessedMessageDto $dto): void;
}
