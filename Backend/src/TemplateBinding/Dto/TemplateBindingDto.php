<?php

declare(strict_types=1);

namespace Tickets\TemplateBinding\Dto;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

/**
 * Привязка шаблонов. Пассивная сущность (как LocationDto): данные + фабрика fromState.
 *
 * email_slug/pdf_slug — slug привязанных шаблонов (join templates.slug); заполняются ТОЛЬКО
 * при загрузке для резолва. В CRUD достаточно email_template_id/pdf_template_id (FK).
 */
class TemplateBindingDto extends AbstractionEntity implements Response
{
    public function __construct(
        protected Uuid $id,
        protected ?string $festival_id,
        protected ?string $order_type,
        protected ?string $ticket_type_id,
        protected ?string $email_template_id,
        protected ?string $pdf_template_id,
        protected bool $is_default,
        protected bool $active,
        protected ?string $email_slug = null,
        protected ?string $pdf_slug = null,
    ) {
    }

    public static function fromState(array $data): self
    {
        return new self(
            empty($data['id']) ? Uuid::random() : new Uuid($data['id']),
            $data['festival_id'] ?? null,
            $data['order_type'] ?? null,
            $data['ticket_type_id'] ?? null,
            $data['email_template_id'] ?? null,
            $data['pdf_template_id'] ?? null,
            (bool) ($data['is_default'] ?? false),
            (bool) ($data['active'] ?? true),
            $data['email_slug'] ?? null,
            $data['pdf_slug'] ?? null,
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getFestivalId(): ?string
    {
        return $this->festival_id;
    }

    public function getOrderType(): ?string
    {
        return $this->order_type;
    }

    public function getTicketTypeId(): ?string
    {
        return $this->ticket_type_id;
    }

    public function getEmailTemplateId(): ?string
    {
        return $this->email_template_id;
    }

    public function getPdfTemplateId(): ?string
    {
        return $this->pdf_template_id;
    }

    public function isDefault(): bool
    {
        return $this->is_default;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    /** slug привязанного шаблона для нужного kind (email|pdf) или null. */
    public function slugForKind(string $kind): ?string
    {
        return $kind === 'pdf' ? $this->pdf_slug : $this->email_slug;
    }

    /** Специфичность: чем больше непустых полей ключа, тем выше приоритет (ticket > order > festival). */
    public function specificity(): int
    {
        return ($this->ticket_type_id !== null ? 4 : 0)
            + ($this->order_type !== null ? 2 : 0)
            + ($this->festival_id !== null ? 1 : 0);
    }

    /** Подходит ли привязка под запрос (NULL-поле = wildcard «любой»). */
    public function matches(?string $festivalId, ?string $orderType, ?string $ticketTypeId): bool
    {
        return ($this->festival_id === null || $this->festival_id === $festivalId)
            && ($this->order_type === null || $this->order_type === $orderType)
            && ($this->ticket_type_id === null || $this->ticket_type_id === $ticketTypeId);
    }

    /** Для записи в БД: без join-полей slug и без timestamps (их ставит Eloquent). */
    public function toArrayForCreate(): array
    {
        $result = $this->toArray();
        unset($result['email_slug'], $result['pdf_slug'], $result['created_at'], $result['updated_at']);

        return $result;
    }
}
