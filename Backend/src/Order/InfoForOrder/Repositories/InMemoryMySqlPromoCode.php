<?php
declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Repositories;

use App\Models\Ordering\InfoForOrder\PromoCodeModel;
use Tickets\Order\InfoForOrder\Response\PromoCodeDto;

class InMemoryMySqlPromoCode implements PromoCodeInterface
{
    public function __construct(
        private PromoCodeModel $model,
    ) {
    }


    public function find(string $name): ?PromoCodeDto
    {
        $promoCode = $this->model::whereName($name)->first()?->toArray();
        if (!is_null($promoCode)) {
            return PromoCodeDto::fromState($promoCode);
        }

        return null;
    }
}
