<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Service;

use Tickets\Order\InfoForOrder\Application\GetPriceByTicketType\GetPriceByTicketType;
use Tickets\Order\InfoForOrder\Application\SearchPromoCode\IsCorrectPromoCode;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Shared\Domain\ValueObject\Uuid;

class PriceService
{
    public function __construct(
        private GetPriceByTicketType $getPriceByTicketType,
        private IsCorrectPromoCode $isCorrectPromoCode,
    ) {
    }

    public function getPriceDto(Uuid $ticketTypeId, int $count, ?string $promoCode = null): PriceDto
    {
        $priceByType = $this->getPriceByTicketType->getPrice($ticketTypeId);
        $totalPrice = $priceByType->getPrice() * ($priceByType->isGroupType() ? 1 : $count);

        return new PriceDto(
            $totalPrice,
            $this->isCorrectPromoCode->findPromoCode($promoCode)?->getDiscount() ?? 0.00
        );
    }
}
