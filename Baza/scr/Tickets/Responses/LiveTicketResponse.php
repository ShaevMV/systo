<?php

declare(strict_types=1);

namespace Baza\Tickets\Responses;

use Baza\Shared\Domain\ValueObject\Status;
use Baza\Tickets\Applications\Scan\TicketResponseInterface;
use Baza\Tickets\Services\DefineService;
use Baza\Tickets\ValueObject\Color;
use Carbon\Carbon;

class LiveTicketResponse implements TicketResponseInterface
{
    public function __construct(
        protected int     $kilter,
        protected ?Status $status,
        protected ?string $comment = null,
        protected ?int    $change_id = null,
        protected ?Carbon $date_change = null
    )
    {
    }

    public function toArray(): array
    {
        return [
            'type' => DefineService::LIVE_TICKET,
            'kilter' => $this->kilter,
            'comment' => $this->comment,
            'status_human' => $this->status->getHumanStatus(),
            'status' => (string)$this->status,
            'change_id' => $this->change_id ?? null,
            'date_change' => $this->date_change?->format('d M Y H:i:s'),
            'color' => Color::COLOR_LIVE,
        ];
    }

    public static function fromState(array $data): self
    {
        $date_change = !is_null($data['date_change'] ?? null) ? Carbon::parse($data['date_change']) : null;

        return new self(
            $data['kilter'],
            new Status($data['status']),
            $data['comment'] ?? null,
            $data['change_id'] ?? null,
            $date_change
        );
    }
}
