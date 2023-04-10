<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Search\ElTicket;

use Baza\Tickets\Applications\Search\TicketResponseInterface;
use Carbon\Carbon;
use Baza\Shared\Domain\ValueObject\Uuid;

class ElTicketResponse implements TicketResponseInterface
{
    public function __construct(
        protected int     $kilter,
        protected Uuid    $uuid,
        protected string  $name,
        protected string  $email,
        protected string  $phone,
        protected Carbon  $date_order,
        protected ?int    $change_id = null,
        protected ?Carbon $date_change = null
    )
    {
    }

    public function toArray(): array
    {
        return [
            'kilter' => $this->kilter,
            'uuid' => $this->uuid->value(),
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_order' => $this->date_order->toString(),
            'change_id' => $this->change_id ?? null,
            'date_change' => $this->date_change?->toString(),
        ];
    }

    public static function fromState(array $data): self
    {
        $date_change = !is_null($data['date_change'] ?? null) ? Carbon::parse($data['date_change']) : null;

        return new self(
            $data['kilter'],
            new Uuid($data['uuid']),
            $data['name'],
            $data['email'],
            $data['phone'],
            Carbon::parse($data['date_order']),
            $data['change_id'] ?? null,
            $date_change,
        );
    }
}
