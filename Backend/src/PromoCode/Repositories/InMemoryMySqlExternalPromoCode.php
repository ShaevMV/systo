<?php
declare(strict_types=1);

namespace Tickets\PromoCode\Repositories;

use App\Models\Ordering\InfoForOrder\ExternalPromoCodeModel;
use Illuminate\Support\Facades\DB;
use Throwable;
use Tickets\PromoCode\Response\ExternalPromoCodeDto;
use Shared\Domain\ValueObject\Uuid;

class InMemoryMySqlExternalPromoCode implements ExternalPromoCodeInterface
{
    public function __construct(
        private ExternalPromoCodeModel $model,
    )
    {
    }


    /**
     * @throws Throwable
     */
    public function insertOrder(Uuid $ticketTypeId): bool
    {

        try {
            DB::beginTransaction();
            $rawModel = $this->model::whereOrderTicketsId(null)->first();
            $rawModel?->update([
                'order_tickets_id' => $ticketTypeId->value()
            ]);

            DB::commit();
            return true;
        } catch (Throwable $throwable) {
            DB::rollBack();
            throw $throwable;
        }
    }

    public function find(Uuid $ticketTypeId): ?ExternalPromoCodeDto
    {
        $promocode = $this->model::whereOrderTicketsId($ticketTypeId->value())->first()?->toArray();
        if(!empty($promocode['promocode'])) {
            return new ExternalPromoCodeDto($promocode['promocode']);
        }

        return null;
    }
}
