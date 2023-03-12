<?php

declare(strict_types=1);

namespace App\Http\Controllers\Festival;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
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
        private IsCorrectPromoCode                $isCorrectPromoCode,
        private GetTicketType                     $getTicketType,
        private GetPriceList                      $getPriceList,
    )
    {
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
        $price = $this->getTicketType->getPrice(new Uuid($request->input('typeOrder')), new Carbon());

        if ($price->isGroupType()) {
            return PromoCodeDto::fromGroupTicket()->toArray();
        }

        return $this->isCorrectPromoCode
            ->findPromoCode(
                $promoCode,
                $price->getPrice()
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
