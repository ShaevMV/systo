<?php

declare(strict_types=1);

namespace Tickets\TicketType\Application\GetItem;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

class TicketTypeGetItemQuery implements Query
{
    public function __construct(
        private Uuid $id,
    )
    {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}
