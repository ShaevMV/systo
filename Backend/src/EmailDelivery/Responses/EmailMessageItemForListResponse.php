<?php

declare(strict_types=1);

namespace Tickets\EmailDelivery\Responses;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

/**
 * Облегчённая проекция письма для списка админки «Доставка писем» (без meta/mailable — тяжёлые).
 * error включён намеренно: в списке сразу видно «где застряло». snake_case как у qr-проекций.
 */
class EmailMessageItemForListResponse extends AbstractionEntity implements Response
{
    public function __construct(
        protected Uuid $id,
        protected string $event,
        protected string $recipient,
        protected ?string $subject,
        protected string $status,
        protected int $attempts,
        protected ?string $error,
        protected string $source,
        protected ?string $festival_id,
        protected ?string $aggregate_type,
        protected ?string $aggregate_id,
        protected ?string $sent_at,
        protected ?string $opened_at,
        protected ?string $created_at,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromState(array $data): self
    {
        return new self(
            new Uuid((string) $data['id']),
            (string) ($data['event'] ?? ''),
            (string) ($data['recipient'] ?? ''),
            $data['subject'] ?? null,
            (string) ($data['status'] ?? ''),
            (int) ($data['attempts'] ?? 0),
            $data['error'] ?? null,
            (string) ($data['source'] ?? ''),
            isset($data['festival_id']) ? (string) $data['festival_id'] : null,
            $data['aggregate_type'] ?? null,
            isset($data['aggregate_id']) ? (string) $data['aggregate_id'] : null,
            isset($data['sent_at']) ? (string) $data['sent_at'] : null,
            isset($data['opened_at']) ? (string) $data['opened_at'] : null,
            isset($data['created_at']) ? (string) $data['created_at'] : null,
        );
    }
}
