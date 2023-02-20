<?php

namespace Tickets\Order\OrderTicket\Responses;

use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Shared\Domain\Entity\AbstractionEntity;

class TotalNumberResponse extends AbstractionEntity implements Response
{
    public function __construct(
        protected int $totalCount = 0,
        protected int $totalCountToPaid = 0,
        protected int $countTickets = 0,
        protected float $totalAmount = 0.00,
    ) {
    }
}
