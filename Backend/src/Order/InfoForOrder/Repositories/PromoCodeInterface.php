<?php

declare(strict_types = 1);

namespace Tickets\Order\InfoForOrder\Repositories;

use Tickets\Order\InfoForOrder\Response\PromoCodeDto;

interface PromoCodeInterface
{
    public function find(string $name):?PromoCodeDto;
}
