<?php

declare(strict_types=1);

namespace Tickets\Location\Application\GetItem;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

class LocationGetItemQuery implements Query
{
    public function __construct(
        private Uuid $id
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}
