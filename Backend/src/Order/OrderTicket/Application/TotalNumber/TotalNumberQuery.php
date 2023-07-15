<?php

namespace Tickets\Order\OrderTicket\Application\TotalNumber;

use Tickets\Order\OrderTicket\Responses\ListResponse;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemForListResponse;
use Shared\Domain\Bus\Query\Query;

class TotalNumberQuery implements Query
{
    public function __construct(
        private ListResponse $listResponse
    ) {
    }

    /**
     * @return OrderTicketItemForListResponse[]
     */
    public function getOrderList(): array
    {
        return $this->listResponse->getOrderList();
    }
}
