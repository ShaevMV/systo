<?php

namespace Tickets\Order\OrderTicket\Application\GetOrderList;

use Nette\Utils\JsonException;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemForList;
use Tickets\Shared\Domain\Bus\Query\Response;

class ListResponse implements Response
{
    /**
     * @param  OrderTicketItemForList[]  $orderList
     */
    public function __construct(
        private array $orderList
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
}
