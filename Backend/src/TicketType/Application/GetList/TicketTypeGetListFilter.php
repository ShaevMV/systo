<?php

declare(strict_types=1);

namespace Tickets\TicketType\Application\GetList;


class TicketTypeGetListFilter
{
    public function __construct(
        private ?string $name,
        private ?int    $price,
        private ?bool   $active,
        private ?bool   $is_live_ticket,
    )
    {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPrice(): ?string
    {
        return $this->price;
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
        return new self(
            $data['name'] ?? null,
            $data['price'] ?? null,
            ($data['active'] ?? null) === null ? null : $data['active'] === 'true',
            ($data['is_live_ticket'] ?? null) === null ? null : $data['is_live_ticket'] === 'true',
        );
    }
}
