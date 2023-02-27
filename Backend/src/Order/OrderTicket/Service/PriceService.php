<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Service;

use Carbon\Carbon;
use Tickets\Order\InfoForOrder\Application\GetTicketType\GetTicketType;
use Tickets\Order\InfoForOrder\Application\SearchPromoCode\IsCorrectPromoCode;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Shared\Domain\ValueObject\Uuid;

class PriceService
{
    public function __construct(
        private GetTicketType      $getPriceByTicketType,
        private IsCorrectPromoCode $isCorrectPromoCode,
    )
    {
    }

    public function getPriceDto(
        Uuid    $ticketTypeId,
        int     $count,
        ?string $promoCode = null,
        ?Carbon $dateTime = null,
    ): PriceDto
    {
        $priceByType = $this->getPriceByTicketType->getPrice(
            $ticketTypeId,
                $dateTime ?? new Carbon()
        );
        $totalPrice = $priceByType->getPrice() * ($priceByType->isGroupType() ? 1 : $count);
        $discount = $this->isCorrectPromoCode->findPromoCode($promoCode)?->getDiscount() ?? 0.00;

        return new PriceDto(
            $totalPrice,
            $discount * $count
        );
    }
}
