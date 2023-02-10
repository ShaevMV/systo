<?php
declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\GetTicket;

use Tickets\Shared\Domain\Bus\Query\Query;
use Tickets\Shared\Domain\ValueObject\Uuid;

class GetTicketQuery implements Query
{
    public function __construct(
        private Uuid $id
    ){
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

}
