<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Search\LiveTicket;

use Baza\Tickets\Applications\Search\DefineService;
use Baza\Tickets\Applications\Search\TicketResponseInterface;
use Carbon\Carbon;

class LiveTicketResponse implements TicketResponseInterface
{
    public function __construct(
        protected int     $kilter,
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
            'change_id' => $this->change_id ?? null,
            'date_change' => $this->date_change?->format('d M Y H:i:s'),
        ];
    }

    public static function fromState(array $data): self
    {
        $date_change = !is_null($data['date_change'] ?? null) ? Carbon::parse($data['date_change']) : null;

        return new self(
            $data['kilter'],
            $data['comment'] ?? null,
            $data['change_id'] ?? null,
            $date_change
        );
    }
}
