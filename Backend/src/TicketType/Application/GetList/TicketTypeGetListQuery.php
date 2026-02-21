<?php

declare(strict_types=1);

namespace Tickets\TicketType\Application\GetList;

use Shared\Domain\Bus\Query\Query;

class TicketTypeGetListQuery implements Query
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
            $data['active'] === null ? null : (bool)$data['active'],
            $data['is_live_ticket'] === null ? null : (bool)$data['is_live_ticket'],
        );
    }
}
