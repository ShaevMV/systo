<?php

declare(strict_types=1);

namespace App\Http\Controllers\Festival;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tickets\Ordering\InfoForOrder\Application\GetInfoForOrder\AllInfoForOrderingTicketsSearcher;
use Tickets\Ordering\InfoForOrder\Application\SearchPromoCode\IsCorrectPromoCode;

class OrderingTicketsController extends Controller
{
    public function __construct(
        private AllInfoForOrderingTicketsSearcher $allInfoForOrderingTicketsSearcher,
        private IsCorrectPromoCode $isCorrectPromoCode,
    ) {
    }

    public function getInfoForOrder(): array
    {
        return $this->allInfoForOrderingTicketsSearcher->getInfo()->toArray();
    }

    public function findPromoCode(string $promoCode): array
    {
        return $this->isCorrectPromoCode->findPromoCode($promoCode)?->toArray() ?? [];
    }
}
