<?php

declare(strict_types=1);

namespace App\Http\Controllers\Festival;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nette\Utils\JsonException;
use Tickets\Order\InfoForOrder\Application\GetInfoForOrder\AllInfoForOrderingTicketsSearcher;
use Tickets\Order\InfoForOrder\Application\GetPriceList\GetPriceList;
use Tickets\Order\InfoForOrder\Application\GetTicketType\GetTicketType;
use Tickets\PromoCode\Application\GetPromoCodes\GetPromoCodes;
use Tickets\PromoCode\Application\SearchPromoCode\IsCorrectPromoCode;
use Tickets\PromoCode\Response\PromoCodeDto;
use Tickets\Shared\Domain\ValueObject\Uuid;

class OrderingTicketsController extends Controller
{
    public function __construct(
        private AllInfoForOrderingTicketsSearcher $allInfoForOrderingTicketsSearcher,
        private IsCorrectPromoCode                $isCorrectPromoCode,
        private GetTicketType                     $getTicketType,
        private GetPriceList                      $getPriceList,
        private GetPromoCodes                     $getPromoCodes,
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
    public function getListPromoCode(): array
    {
        return $this->getPromoCodes->getList()->toArray();
    }

    /**
     * @throws JsonException
     */
    public function getItemPromoCode(?string $idPromoCode): JsonResponse
    {
        if(!is_null($idPromoCode)) {
            if($result = $this->getPromoCodes->getItem(new Uuid($idPromoCode))) {
                return response()->json($result->toArray());
            }

            return response()->json([
                'errors' => ['error' => 'Промокод не найден']
            ], 404);
        }

        return response()->json([]);
    }

    /**
     * @throws JsonException
     */
    public function getPriceList(): array
    {
        return $this->getPriceList->getAllPrice()->toArray();
    }
}
