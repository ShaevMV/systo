<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Service;

use Shared\Domain\ValueObject\Uuid;

class TicketService
{
    /**
     * @param array $guests
     * @param Uuid[] $festivalIds
     * @return array
     */
    public function initFestivalId(array $guests, array $festivalIds):array
    {
        $result = [];
        foreach ($festivalIds as $festivalId) {
            foreach ($guests as $guest) {
                $guest['festival_id'] = $festivalId->value();
                $result[] = $guest;
            }
        }

        return $result;
    }
}
