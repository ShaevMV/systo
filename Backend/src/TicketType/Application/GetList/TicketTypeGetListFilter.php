<?php

declare(strict_types=1);

namespace Tickets\TicketType\Application\GetList;


use Shared\Domain\ValueObject\Uuid;

class TicketTypeGetListFilter
{
    public function __construct(
        private ?string $name = null,
        private ?bool   $active = null,
        private ?bool   $is_live_ticket = null,
        private ?Uuid   $festival_id = null
    )
    {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function getIsLiveTicket(): ?bool
    {
        return $this->is_live_ticket;
    }

    public static function fromState(array $data): self
    {
        $festivalId = null;
        if ($data['festival_id'] ?? null) {
            $festivalId = new Uuid($data['festival_id']);
        }

        return new self(
            $data['name'] ?? null,
            ($data['active'] ?? null) === null ? null : $data['active'] === 'true',
            ($data['is_live_ticket'] ?? null) === null ? null : $data['is_live_ticket'] === 'true',
            $festivalId,
        );
    }

    public function getFestivalId(): ?Uuid
    {
        return $this->festival_id;
    }
}
