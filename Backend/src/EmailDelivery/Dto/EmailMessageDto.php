<?php

declare(strict_types=1);

namespace Tickets\EmailDelivery\Dto;

use Illuminate\Support\Carbon;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;
use Tickets\EmailDelivery\Application\EmailContext;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\EmailDelivery\Domain\ValueObject\EmailStatus;

/**
 * Письмо в системе доставки (Ф2). Пассивная сущность (как QrOrderDto): данные + фабрики.
 * Не несёт сериализованный Mailable (он тяжёлый и хранится отдельной колонкой) — DTO безопасен
 * для отдачи в API (admin). Спека: .claude/specs/email-delivery-system.md (Часть 2).
 */
class EmailMessageDto extends AbstractionEntity implements Response
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        protected Uuid $id,
        protected string $event,
        protected string $recipient,
        protected ?string $subject,
        protected ?string $template_slug,
        protected string $status,
        protected int $attempts,
        protected ?string $error,
        protected string $source,
        protected ?string $aggregate_type,
        protected ?string $aggregate_id,
        protected ?string $festival_id,
        protected string $tracking_token,
        protected ?string $provider_message_id,
        protected array $meta,
        protected ?Carbon $sent_at,
        protected ?Carbon $opened_at,
        protected ?Carbon $created_at = null,
        protected ?Carbon $updated_at = null,
    ) {
    }

    /** Новое письмо в статусе queued из контекста отправки. */
    public static function queued(Uuid $id, string $event, EmailContext $ctx, string $slug, string $token): self
    {
        return new self(
            $id,
            $event,
            $ctx->recipient,
            null,
            $slug,
            EmailStatus::QUEUED,
            0,
            null,
            $ctx->source,
            $ctx->aggregateType,
            $ctx->aggregateId,
            $ctx->festivalId,
            $token,
            null,
            $ctx->meta,
            null,
            null,
        );
    }

    /**
     * Сборка из строки БД. Даты — уже Carbon (Eloquent-каст datetime), не оборачиваем повторно.
     *
     * @param array<string, mixed> $data
     */
    public static function fromState(array $data): self
    {
        return new self(
            new Uuid((string) $data['id']),
            (string) ($data['event'] ?? ''),
            (string) ($data['recipient'] ?? ''),
            $data['subject'] ?? null,
            $data['template_slug'] ?? null,
            (string) ($data['status'] ?? EmailStatus::QUEUED),
            (int) ($data['attempts'] ?? 0),
            $data['error'] ?? null,
            (string) ($data['source'] ?? ''),
            $data['aggregate_type'] ?? null,
            isset($data['aggregate_id']) ? (string) $data['aggregate_id'] : null,
            isset($data['festival_id']) ? (string) $data['festival_id'] : null,
            (string) ($data['tracking_token'] ?? ''),
            $data['provider_message_id'] ?? null,
            is_array($data['meta'] ?? null) ? $data['meta'] : [],
            empty($data['sent_at']) ? null : Carbon::parse((string) $data['sent_at']),
            empty($data['opened_at']) ? null : Carbon::parse((string) $data['opened_at']),
            empty($data['created_at']) ? null : Carbon::parse((string) $data['created_at']),
            empty($data['updated_at']) ? null : Carbon::parse((string) $data['updated_at']),
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTrackingToken(): string
    {
        return $this->tracking_token;
    }

    public function getEventLabelSlug(): ?string
    {
        return $this->template_slug;
    }

    /** Поля для записи в БД (без join/computed). meta — массив (Eloquent сам кодирует). */
    public function toArrayForCreate(): array
    {
        return [
            'id' => $this->id->value(),
            'event' => $this->event,
            'recipient' => $this->recipient,
            'subject' => $this->subject,
            'template_slug' => $this->template_slug,
            'status' => $this->status,
            'attempts' => $this->attempts,
            'error' => $this->error,
            'source' => $this->source,
            'aggregate_type' => $this->aggregate_type,
            'aggregate_id' => $this->aggregate_id,
            'festival_id' => $this->festival_id,
            'tracking_token' => $this->tracking_token,
            'provider_message_id' => $this->provider_message_id,
            'meta' => $this->meta,
        ];
    }

    /** Дефолтный slug для события (через каталог) — на случай, если template_slug не задан. */
    public function defaultSlugForEvent(): ?string
    {
        return EmailEvent::defaultSlug($this->event);
    }
}
