<?php

declare(strict_types=1);

namespace Tickets\Integration\Qr\Dto;

/**
 * Данные для отметки входящего события обработанным (дедуп qr → org).
 * Собирается из конверта события (EventEnvelope) на уровне приёма.
 */
final class ProcessedMessageDto
{
    public function __construct(
        public readonly string $idempotencyKey,
        public readonly string $eventType,
        public readonly string $source,
        public readonly string $traceId,
    ) {
    }
}
