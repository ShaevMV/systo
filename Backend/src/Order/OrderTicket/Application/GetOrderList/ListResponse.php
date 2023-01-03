<?php

namespace Tickets\Order\OrderTicket\Application\GetOrderList;

use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Shared\Domain\Bus\Query\Response;

class ListResponse implements Response
{
    /**
     * @param  OrderTicketDto[]  $orderList
     */
    public function __construct(
        private array $orderList
    ) {
    }


    /**
     * @throws \JsonException
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->orderList as $item) {
            $result[] = $item->toArray();
        }

        return $result;
    }
}
