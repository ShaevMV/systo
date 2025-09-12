<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Service;

use Carbon\Carbon;
use Tickets\Order\InfoForOrder\Application\GetTicketType\GetTicketType;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\PromoCode\Application\SearchPromoCode\IsCorrectPromoCode;
use Shared\Domain\ValueObject\Uuid;

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

        $discount = $this->isCorrectPromoCode->findPromoCode(
            !empty($promoCode) ? trim($promoCode) : null,
            $priceByType->getPrice(),
            $ticketTypeId,
            new Uuid('9d679bcf-b438-4ddb-ac04-023fa9bff4b7'),
        )?->getDiscount() ?? 0.00;

        return new PriceDto(
            (int)$priceByType->getPrice(),
            ($priceByType->isGroupType() ? 1 : $count),
            $discount * $count
        );
    }
}
