<?php

declare(strict_types=1);

namespace Tickets\BazaDelivery\Dto;

use Illuminate\Support\Carbon;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;
use Tickets\BazaDelivery\Application\BazaDeliveryContext;
use Tickets\BazaDelivery\Domain\ValueObject\BazaDeliveryStatus;

/**
 * Доставка билета в Baza (одна строка на (ticket_id, target)). Пассивная сущность (как QrOrderDto):
 * данные + фабрики. Текущий статус доставки; история ВСЕХ попыток — в domain_history.
 * Содержит ПДн (name/email) → отдаётся только admin. Спека: .claude/specs/baza-delivery-async-prompt.md.
 */
class BazaDeliveryDto extends AbstractionEntity implements Response
{
    public function __construct(
        protected Uuid $id,
        protected Uuid $ticket_id,
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
        protected ?Carbon $delivered_at,
        protected ?Carbon $created_at = null,
        protected ?Carbon $updated_at = null,
    ) {
    }

    /** Новая доставка в статусе queued из контекста. target ∈ el_tickets/spisok_tickets/live_tickets/auto. */
    public static function queued(Uuid $id, Uuid $ticketId, string $target, BazaDeliveryContext $ctx): self
    {
        return new self(
            $id,
            $ticketId,
            $ctx->orderId,
            $target,
            BazaDeliveryStatus::QUEUED,
            0,
            null,
            $ctx->name,
            $ctx->email,
            $ctx->number,
            $ctx->festivalId,
            $ctx->source,
            null,
            null,
            null,
        );
    }

    /**
     * Сборка из строки БД. Даты — уже Carbon (Eloquent-каст datetime), при ручной сборке — parse.
     *
     * @param array<string, mixed> $data
     */
    public static function fromState(array $data): self
    {
        return new self(
            new Uuid((string) $data['id']),
            new Uuid((string) $data['ticket_id']),
            isset($data['order_id']) ? (string) $data['order_id'] : null,
            (string) ($data['target'] ?? ''),
            (string) ($data['status'] ?? BazaDeliveryStatus::QUEUED),
            (int) ($data['attempts'] ?? 0),
            $data['error'] ?? null,
            $data['name'] ?? null,
            $data['email'] ?? null,
            isset($data['number']) ? (int) $data['number'] : null,
            isset($data['festival_id']) ? (string) $data['festival_id'] : null,
            (string) ($data['source'] ?? ''),
            empty($data['delivered_at']) ? null : Carbon::parse((string) $data['delivered_at']),
            empty($data['created_at']) ? null : Carbon::parse((string) $data['created_at']),
            empty($data['updated_at']) ? null : Carbon::parse((string) $data['updated_at']),
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTicketId(): Uuid
    {
        return $this->ticket_id;
    }

    public function getOrderId(): ?string
    {
        return $this->order_id;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    /** Поля для записи в БД (без computed). */
    public function toArrayForCreate(): array
    {
        return [
            'id' => $this->id->value(),
            'ticket_id' => $this->ticket_id->value(),
            'order_id' => $this->order_id,
            'target' => $this->target,
            'status' => $this->status,
            'attempts' => $this->attempts,
            'error' => $this->error,
            'name' => $this->name,
            'email' => $this->email,
            'number' => $this->number,
            'festival_id' => $this->festival_id,
            'source' => $this->source,
        ];
    }
}
