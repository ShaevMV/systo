<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Search\ElTicket;

use Baza\Shared\Domain\Bus\Query\Response;
use Carbon\Carbon;
use Tickets\Shared\Domain\ValueObject\Uuid;

class ElTicketResponse implements Response
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
}
