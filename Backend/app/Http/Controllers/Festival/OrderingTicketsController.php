<?php

declare(strict_types=1);

namespace App\Http\Controllers\Festival;

use App\Http\Controllers\Controller;
use Nette\Utils\JsonException;
use Tickets\Order\InfoForOrder\Application\GetInfoForOrder\AllInfoForOrderingTicketsSearcher;
use Tickets\Order\InfoForOrder\Application\GetPriceList\GetPriceList;
use Tickets\Order\InfoForOrder\Application\GetTicketType\GetTicketType;
use Tickets\Order\InfoForOrder\Application\SearchPromoCode\IsCorrectPromoCode;
use Illuminate\Http\Request;
use Tickets\Order\InfoForOrder\Response\PromoCodeDto;
use Tickets\Shared\Domain\ValueObject\Uuid;

class OrderingTicketsController extends Controller
{
    public function __construct(
        private AllInfoForOrderingTicketsSearcher $allInfoForOrderingTicketsSearcher,
        private IsCorrectPromoCode $isCorrectPromoCode,
        private GetTicketType $getTicketType,
        private GetPriceList $getPriceList,
    ) {
    }

    /**
     * @throws JsonException
     */
    public function getInfoForOrder(): array
    {
        return $this->allInfoForOrderingTicketsSearcher
            ->getInfo()
            ->toArray();
    }

    /**
     * @throws JsonException
     */
    public function findPromoCode(Request $request, string $promoCode): array
    {
        if($this->getTicketType->isGroupTicket(new Uuid($request->input('typeOrder')))) {
            return PromoCodeDto::fromGroupTicket()->toArray();
        }

        return $this->isCorrectPromoCode
            ->findPromoCode(
                $promoCode,
            )->toArray();
    }

    /**
     * @throws JsonException
     */
    public function getPriceList(): array
    {
        return $this->getPriceList->getAllPrice()->toArray();
    }
}
