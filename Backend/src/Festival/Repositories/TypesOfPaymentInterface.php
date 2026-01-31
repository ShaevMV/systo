<?php

declare(strict_types = 1);

namespace Tickets\Festival\Repositories;

use Tickets\Festival\Response\TypesOfPaymentDto;

interface TypesOfPaymentInterface
{
    /**
     * @return TypesOfPaymentDto[]
     */
    public function getList(bool $isAdmin = false):array;
}
