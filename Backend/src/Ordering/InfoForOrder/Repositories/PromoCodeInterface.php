<?php

declare(strict_types = 1);

namespace Tickets\Ordering\InfoForOrder\Repositories;

use Tickets\Ordering\InfoForOrder\Response\PromoCodeDto;

interface PromoCodeInterface
{
    public function find(string $name):?PromoCodeDto;
}
