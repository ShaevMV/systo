<?php

declare(strict_types=1);

namespace Tickets\BazaDelivery\Responses;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

/**
 * Облегчённая проекция доставки билета в Baza для списка админки «Доставка в baza».
 * error включён намеренно: в списке сразу видно «где застряло». snake_case как у qr-проекций.
 */
class BazaDeliveryItemForListResponse extends AbstractionEntity implements Response
{
    public function __construct(
        protected Uuid $id,
        protected string $ticket_id,
        protected ?string $order_id,
        protected string $target,
        protected string $status,
        protected int $attempts,
        protected ?string $error,
        protected ?string $name,
        protected ?string $email,
        protected ?int $number,
        protected ?string $festival_id,
        protected string $source,
        protected ?string $delivered_at,
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
            (string) ($data['ticket_id'] ?? ''),
            isset($data['order_id']) ? (string) $data['order_id'] : null,
            (string) ($data['target'] ?? ''),
            (string) ($data['status'] ?? ''),
            (int) ($data['attempts'] ?? 0),
            $data['error'] ?? null,
            $data['name'] ?? null,
            $data['email'] ?? null,
            isset($data['number']) ? (int) $data['number'] : null,
            isset($data['festival_id']) ? (string) $data['festival_id'] : null,
            (string) ($data['source'] ?? ''),
            isset($data['delivered_at']) ? (string) $data['delivered_at'] : null,
            isset($data['created_at']) ? (string) $data['created_at'] : null,
        );
    }
}
