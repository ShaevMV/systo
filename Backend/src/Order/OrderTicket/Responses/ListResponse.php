<?php

namespace Tickets\Order\OrderTicket\Responses;

use Nette\Utils\JsonException;
use Tickets\Shared\Domain\Bus\Query\Response;

class ListResponse implements Response
{
    /**
     * @param  OrderTicketItemForListResponse[]  $orderList
     */
    public function __construct(
        private array $orderList = []
    ) {
    }

    /**
     * @throws JsonException
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->orderList as $item) {
            $result[] = $item->toArray();
        }

        return $result;
    }

    public function getOrderList(): array
    {
        return $this->orderList;
    }
}
