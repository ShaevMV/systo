<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Application\GetItem;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

class TypesOfPaymentGetItemQuery implements Query
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
