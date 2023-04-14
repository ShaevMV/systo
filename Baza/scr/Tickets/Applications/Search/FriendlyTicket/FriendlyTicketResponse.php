<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Search\FriendlyTicket;

use Baza\Tickets\Applications\Search\DefineService;
use Baza\Tickets\Applications\Search\TicketResponseInterface;
use Carbon\Carbon;

class FriendlyTicketResponse implements TicketResponseInterface
{
    public function __construct(
        protected int     $kilter,
        protected string  $name,
        protected string  $project,
        protected string  $email,
        protected Carbon  $date_order,
        protected ?int    $change_id = null,
        protected ?Carbon $date_change = null
    )
    {
    }

    public function toArray(): array
    {
        return [
            'type' => DefineService::FRIENDLY_TICKET,
            'kilter' => $this->kilter,
            'name' => $this->name,
            'email' => $this->email,
            'project' => $this->project,
            'date_order' => $this->date_order->format('d M Y'),
            'change_id' => $this->change_id ?? null,
            'date_change' => $this->date_change?->format('d M Y H:i:s'),
        ];
    }

    public static function fromState(array $data): self
    {
        $date_change = !is_null($data['date_change'] ?? null) ? Carbon::parse($data['date_change']) : null;

        return new self(
            $data['kilter'],
            $data['name'],
            $data['project'],
            $data['email'],
            Carbon::parse($data['date_order']),
            $data['change_id'] ?? null,
            $date_change
        );
    }
}