<?php

declare(strict_types = 1);

namespace Tickets\Festival\Repositories;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Festival\Response\TypesOfPaymentDto;

interface TypesOfPaymentInterface
{
    /**
     * @return TypesOfPaymentDto[]
     */
    public function getList(bool $isAdmin = false, ?Uuid $ticketTypeId = null):array;
}
