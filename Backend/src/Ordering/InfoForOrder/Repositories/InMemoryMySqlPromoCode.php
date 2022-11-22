<?php
declare(strict_types=1);

namespace Tickets\Ordering\InfoForOrder\Repositories;

use App\Models\Tickets\Ordering\InfoForOrder\Models\PromoCode;
use Tickets\Ordering\InfoForOrder\Response\PromoCodeDto;

final class InMemoryMySqlPromoCode implements PromoCodeInterface
{
    public function __construct(
      private PromoCode $model,
    ) {
    }


    public function find(string $name): ?PromoCodeDto
    {
        $promoCode = $this->model::whereName($name)->first()?->toArray();
        if(!is_null($promoCode)) {
            return PromoCodeDto::fromState($promoCode);
        }

        return null;
    }
}
