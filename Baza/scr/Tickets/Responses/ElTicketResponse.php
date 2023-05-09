<?php

declare(strict_types=1);

namespace Baza\Tickets\Responses;

use Baza\Shared\Domain\ValueObject\Status;
use Baza\Shared\Domain\ValueObject\Uuid;
use Baza\Tickets\Applications\Scan\TicketResponseInterface;
use Baza\Tickets\Services\DefineService;
use Baza\Tickets\ValueObject\Color;
use Carbon\Carbon;

class ElTicketResponse implements TicketResponseInterface
{
    public function __construct(
        protected int     $kilter,
        protected Uuid    $uuid,
        protected string  $name,
        protected string  $email,
        protected string  $phone,
        protected string $city,
        protected Status  $status,
        protected Carbon  $date_order,
        protected ?string $comment = null,
        protected ?int    $change_id = null,
        protected ?Carbon $date_change = null
    )
    {
    }

    public function toArray(): array
    {
        return [
            'type' => DefineService::ELECTRON_TICKET,
            'kilter' => $this->kilter,
            'uuid' => $this->uuid->value(),
            'name' => $this->name,
            'email' => $this->email,
            'city' => $this->city,
            'phone' => $this->phone,
            'comment' => $this->comment,
            'status_human' => $this->status->getHumanStatus(),
            'status' => (string)$this->status,
            'date_order' => $this->date_order->format('d M Y'),
            'change_id' => $this->change_id ?? null,
            'date_change' => $this->date_change?->format('d M Y H:i:s'),
            'color' => Color::COLOR_ELECTRON,
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
            $data['city'],
            new Status($data['status']),
            Carbon::parse($data['date_order']),
            $data['comment'],
            $data['change_id'] ?? null,
            $date_change,
        );
    }
}