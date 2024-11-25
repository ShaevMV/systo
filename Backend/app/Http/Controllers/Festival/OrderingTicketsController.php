<?php

declare(strict_types=1);

namespace App\Http\Controllers\Festival;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePromoCodeRequest;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nette\Utils\JsonException;
use Throwable;
use Tickets\Order\InfoForOrder\Application\GetInfoForOrder\GetInfoForOrder;
use Tickets\Order\InfoForOrder\Application\GetPriceList\GetPriceList;
use Tickets\Order\InfoForOrder\Application\GetTicketType\GetTicketType;
use Tickets\PromoCode\Application\PromoCodes;
use Tickets\PromoCode\Application\SearchPromoCode\IsCorrectPromoCode;
use Tickets\PromoCode\Response\PromoCodeDto;
use Shared\Domain\ValueObject\Uuid;

class OrderingTicketsController extends Controller
{
    public function __construct(
        private GetInfoForOrder    $allInfoForOrderingTicketsSearcher,
        private IsCorrectPromoCode $isCorrectPromoCode,
        private GetTicketType      $getTicketType,
        private PromoCodes         $getPromoCodes,
    )
    {
    }

    /**
     * @throws JsonException
     */
    public function getInfoForOrder(Request $request): array
    {
        if (is_null($request->get('festival_id'))) {
            throw new DomainException('Не задан идентификатор фестиваля');
        }
        $isAdmin = $request->get('is_admin', false);

        return $this->allInfoForOrderingTicketsSearcher
            ->getInfoForOrderingDto(
                new Uuid($request->get('festival_id')),
                (bool)$isAdmin,
            )
            ->toArray();
    }

    /**
     * @throws JsonException
     */
    public function findPromoCode(Request $request, ?string $promoCode = null): array
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
        if (!is_null($idPromoCode)) {
            if ($result = $this->getPromoCodes->getItem(new Uuid($idPromoCode))) {
                return response()->json($result->toArrayForTable());
            }

            return response()->json([
                'errors' => ['error' => 'Промокод не найден']
            ], 404);
        }

        return response()->json([]);
    }

    /**
     * @throws Throwable
     */
    public function savePromoCode(
        CreatePromoCodeRequest $createPromoCodeRequest
    ): JsonResponse
    {
        $id = $this->getPromoCodes->createOrUpdatePromoCode($createPromoCodeRequest->toArray());
        $massage = $createPromoCodeRequest->id ? 'промокод обновлён' : 'промокод добавлен';

        return response()->json([
            'massage' => $massage,
            'id' => $id->value(),
        ]);
    }

    /**
     * @throws JsonException
     */
    public function getPriceList(Request $request): array
    {
        if (is_null($request->get('festival_id'))) {
            throw new DomainException('Не задан идентификатор фестиваля');
        }

        return $this->allInfoForOrderingTicketsSearcher->getAllPrice(new Uuid($request->get('festival_id')))->toArray();
    }
}
