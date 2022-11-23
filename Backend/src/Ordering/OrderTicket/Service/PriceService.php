<?php

declare(strict_types=1);

namespace Tickets\Ordering\OrderTicket\Service;

use Tickets\Ordering\InfoForOrder\Application\GetPriceByTicketType\GetPriceByTicketType;
use Tickets\Ordering\InfoForOrder\Application\SearchPromoCode\IsCorrectPromoCode;
use Tickets\Ordering\OrderTicket\Dto\PriceDto;
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
