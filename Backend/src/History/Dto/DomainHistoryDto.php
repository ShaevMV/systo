<?php

declare(strict_types=1);

namespace Tickets\History\Dto;

use Carbon\Carbon;

final class DomainHistoryDto
{
    public function __construct(
        public readonly string  $aggregateId,
        public readonly string  $aggregateType,
        public readonly string  $eventName,
        public readonly array   $payload,
        public readonly ?string $actorId,
        public readonly string  $actorType,
        public readonly Carbon  $occurredAt,
    ) {
    }
}
