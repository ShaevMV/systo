<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetFestivalList;

use Shared\Domain\Bus\Query\Query;
use Tickets\Festival\Dto\FilterDto;

class GetFestivalListQuery implements Query
{
    public function __construct()
    {
    }
}
