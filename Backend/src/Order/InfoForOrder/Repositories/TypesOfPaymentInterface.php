<?php

declare(strict_types = 1);

namespace Tickets\Ordering\InfoForOrder\Repositories;

use Tickets\Ordering\InfoForOrder\Response\TypesOfPaymentDto;

interface TypesOfPaymentInterface
{
    /**
     * @return TypesOfPaymentDto[]
     */
    public function getList():array;
}
