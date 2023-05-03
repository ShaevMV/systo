<?php

namespace Baza\Tickets\Applications\Enter\LiveTicket;

use Baza\Shared\Domain\Bus\Command\Command;

class LiveTicketCommand implements Command
{
    public function __construct(
        private int $id,
        private int $user_id,
    )
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }
}
