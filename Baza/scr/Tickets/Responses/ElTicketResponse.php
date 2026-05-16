<?php

declare(strict_types=1);

namespace Baza\Tickets\Responses;

use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Baza\Shared\Services\DefineService;
use Baza\Shared\Services\ShowSearchWordService;
use Baza\Tickets\Applications\Scan\TicketResponseInterface;
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
        protected string  $city,
        protected Status  $status,
        protected Carbon  $date_order,
        protected ?string $comment = null,
        protected ?int    $change_id = null,
        protected ?Carbon $date_change = null,
        protected bool  $is_need_seedling = false,
        protected ?Uuid  $type_ticket_id = null,
        protected ?string  $type_ticket = null,
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
            'is_need_seedling' => $this->is_need_seedling,
            'type_ticket_id' => $this->type_ticket_id,
            'type_ticket' => $this->type_ticket,
        ];
    }

    public static function fromState(array $data, ?string $q = null): self
    {
        $date_change = !is_null($data['date_change'] ?? null) ? Carbon::parse($data['date_change']) : null;

        return new self(
            $data['kilter'],
            new Uuid($data['uuid']),
            ShowSearchWordService::insertTag($data['name'], $q),
            ShowSearchWordService::insertTag($data['email'], $q),
            ShowSearchWordService::insertTag($data['phone'], $q),
            $data['city'],
            new Status($data['status']),
            Carbon::parse($data['date_order']),
            ShowSearchWordService::insertTag($data['comment'], $q),
            $data['change_id'] ?? null,
            $date_change,
            (bool) ($data['is_need_seedling'] ?? false),
            empty($data['type_ticket_id']) ? null : new Uuid($data['type_ticket_id']),
            $data['type_ticket'] ?? null
        );
    }
}
