<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use Tickets\Shared\Domain\ValueObject\Uuid;

class Ticket
{
    public function __construct(
        private Uuid   $id,
        private string $name,
        private int    $kilter,
    )
    {
    }
}
