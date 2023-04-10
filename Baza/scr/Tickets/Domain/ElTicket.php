<?php

declare(strict_types=1);

namespace Baza\Tickets\Domain;

use Baza\Tickets\ValueObject\Color;
use Carbon\Carbon;
use Baza\Shared\Domain\ValueObject\Uuid;

class ElTicket extends Ticket
{
    public function __construct(
        int              $kilter,
        protected Uuid   $uuid,
        protected string $name,
        protected string $email,
        protected string $phone,
        protected Carbon $date_order,
        ?int             $change_id = null,
        ?Carbon          $date_change = null)
    {
        parent::__construct($kilter, $change_id, $date_change);
    }

    protected function getColor(): Color
    {
        return new Color(Color::COLOR_ELECTRON);
    }

    public static function fromState(array $data): Ticket
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
            $date_change
        );
    }
}
