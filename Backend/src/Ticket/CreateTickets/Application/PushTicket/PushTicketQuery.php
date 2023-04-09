<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\PushTicket;

use Tickets\Shared\Domain\Bus\Query\Query;
use Tickets\Shared\Domain\ValueObject\Uuid;

class PushTicketQuery implements Query
{
    public function __construct(
        private ?Uuid $id=null
    )
    {
    }

    /**
     * @return Uuid|null
     */
    public function getId(): ?Uuid
    {
        return $this->id;
    }
}
