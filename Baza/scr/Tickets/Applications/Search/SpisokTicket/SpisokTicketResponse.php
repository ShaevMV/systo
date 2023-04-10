<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Search\SpisokTicket;

use Baza\Shared\Domain\Bus\Query\Response;
use Carbon\Carbon;
use Tickets\Shared\Domain\ValueObject\Uuid;

class SpisokTicketResponse implements Response
{
    public function __construct(
        protected int     $kilter,
        protected string  $name,
        protected string  $project,
        protected string  $curator,
        protected string  $email,
        protected Carbon  $date_order,
        protected ?int    $change_id = null,
        protected ?Carbon $date_change = null
    )
    {
    }
}
