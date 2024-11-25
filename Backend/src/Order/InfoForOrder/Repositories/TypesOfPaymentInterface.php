<?php

declare(strict_types = 1);

namespace Tickets\Order\InfoForOrder\Repositories;

use Tickets\Order\InfoForOrder\Response\TypesOfPaymentDto;

interface TypesOfPaymentInterface
{
    /**
     * @return TypesOfPaymentDto[]
     */
    public function getList(bool $isAdmin = false):array;
}
