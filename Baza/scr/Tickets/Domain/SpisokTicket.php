<?php

namespace Baza\Tickets\Domain;

use Baza\Tickets\ValueObject\Color;
use Carbon\Carbon;

class SpisokTicket extends Ticket
{
    public function __construct(
        int              $kilter,
        protected string $name,
        protected string $project,
        protected string $curator,
        protected string $email,
        protected Carbon $date_order,
        ?int             $change_id = null,
        ?Carbon          $date_change = null)
    {
        parent::__construct($kilter, $change_id, $date_change);
    }


    protected function getColor(): Color
    {
        return new Color(Color::COLOR_SPISOK);
    }

    public static function fromState(array $data): Ticket
    {
        $date_change = !is_null($data['date_change'] ?? null) ? Carbon::parse($data['date_change']) : null;

        return new self(
            $data['kilter'],
            $data['name'],
            $data['project'],
            $data['curator'],
            $data['email'],
            Carbon::parse($data['date_order']),
            $data['change_id'] ?? null,
            $date_change
        );
    }
}
