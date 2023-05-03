<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Search\SpisokTicket;

use Baza\Shared\Domain\ValueObject\Status;
use Baza\Tickets\Applications\Search\DefineService;
use Baza\Tickets\Applications\Search\TicketResponseInterface;
use Baza\Tickets\ValueObject\Color;
use Carbon\Carbon;

class SpisokTicketResponse implements TicketResponseInterface
{
    public function __construct(
        protected int     $kilter,
        protected string  $name,
        protected string  $project,
        protected string  $curator,
        protected string  $email,
        protected Carbon  $date_order,
        protected Status $status,
        protected ?string $comment = null,
        protected ?int    $change_id = null,
        protected ?Carbon $date_change = null
    )
    {
    }

    public function toArray(): array
    {
        return [
            'type' => DefineService::SPISOK_TICKET,
            'kilter' => $this->kilter,
            'name' => $this->name,
            'email' => $this->email,
            'curator' => $this->curator,
            'project' => $this->project,
            'status_human' => $this->status->getHumanStatus(),
            'status' => (string)$this->status,
            'comment' => $this->comment,
            'date_order' => $this->date_order->format('d M Y'),
            'change_id' => $this->change_id ?? null,
            'date_change' => $this->date_change?->format('d M Y H:i:s'),
            'color' => Color::COLOR_SPISOK,
        ];
    }

    public static function fromState(array $data): self
    {
        $date_change = !is_null($data['date_change'] ?? null) ? Carbon::parse($data['date_change']) : null;

        return new self(
            $data['kilter'],
            $data['name'],
            $data['project'],
            $data['curator'],
            $data['email'],
            Carbon::parse($data['date_order']),
            new Status($data['status']),
            $data['comment'] ?? null,
            $data['change_id'] ?? null,
            $date_change
        );
    }
}
