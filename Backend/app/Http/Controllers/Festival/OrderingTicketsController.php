<?php

declare(strict_types=1);

namespace App\Http\Controllers\Festival;

use App\Http\Controllers\Controller;
use Nette\Utils\JsonException;
use Tickets\Order\InfoForOrder\Application\GetInfoForOrder\AllInfoForOrderingTicketsSearcher;
use Tickets\Order\InfoForOrder\Application\SearchPromoCode\IsCorrectPromoCode;

class OrderingTicketsController extends Controller
{
    public function __construct(
        private AllInfoForOrderingTicketsSearcher $allInfoForOrderingTicketsSearcher,
        private IsCorrectPromoCode $isCorrectPromoCode,
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
    public function findPromoCode(string $promoCode): ?array
    {
        return $this->isCorrectPromoCode
            ->findPromoCode($promoCode)
            ?->toArray();
    }
}
