<?php

declare(strict_types=1);

namespace Tickets\Option\Dto;

use Shared\Domain\ValueObject\Uuid;

/**
 * Привязка опции к типу билета с описанием.
 *
 * Используется при создании/редактировании опции, чтобы передать
 * пару (ticket_type_id, description) — описание зависит от типа
 * билета и хранится на pivot `option_ticket_type.description`.
 *
 * См. `.claude/specs/ticket-options.md` §3.
 */
final class OptionTicketTypeBindingDto
{
    public function __construct(
        private Uuid $ticketTypeId,
        private ?string $description = null,
    ) {
    }

    public static function fromState(array $data): self
    {
        return new self(
            new Uuid($data['ticket_type_id']),
            $data['description'] ?? null,
        );
    }

    public function getTicketTypeId(): Uuid
    {
        return $this->ticketTypeId;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
