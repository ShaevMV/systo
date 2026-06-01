<?php

declare(strict_types=1);

namespace Tickets\Option\Dto;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

/**
 * Read-модель опции в контексте конкретного типа билета.
 *
 * Используется фронтом (форма покупки билета) — содержит уже
 * подмешанные `price` (актуальная волна из `option_price`) и
 * `description` (с pivot для данного типа билета).
 *
 * Отделена от `OptionDto`, потому что Query Response должна
 * содержать всё что нужно фронту в одном объекте, без N+1.
 *
 * См. `.claude/specs/ticket-options.md`.
 */
final class OptionForTicketTypeView extends AbstractionEntity implements Response
{
    public function __construct(
        protected Uuid $id,
        protected string $name,
        protected int $price,
        protected ?string $description,
        protected bool $active,
    ) {
    }

    public static function fromState(array $data): self
    {
        return new self(
            new Uuid($data['id']),
            $data['name'],
            (int) $data['price'],
            $data['description'] ?? null,
            (bool) ($data['active'] ?? true),
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getActive(): bool
    {
        return $this->active;
    }
}
