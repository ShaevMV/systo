<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\TotalNumber;

use Tickets\Order\OrderTicket\Responses\TotalNumberResponse;
use Shared\Domain\Bus\Query\QueryHandler;

class TotalNumberQueryHandler implements QueryHandler
{
    public function __invoke(TotalNumberQuery $numberQuery): TotalNumberResponse
    {
        $totalCount = 0;
        $totalCountToPaid = 0;
        $totalAmount = 0;
        $totalCountTickets = 0;
        $totalDiscount = 0;

        foreach ($numberQuery->getOrderList() as $itemForListResponse) {
            $totalCount++;
            if ($itemForListResponse->getStatus()->isPaid() || $itemForListResponse->getStatus()->isLiveIssued()) {
                $totalCountToPaid++;
                $totalAmount += $itemForListResponse->getPrice();
                $totalCountTickets+= $itemForListResponse->getCount();
                $totalDiscount += $itemForListResponse->getDiscount();
            }
        }

        return new TotalNumberResponse(
            $totalCount,
            $totalCountToPaid,
            $totalCountTickets,
            $totalAmount,
            $totalDiscount
        );
    }
}
