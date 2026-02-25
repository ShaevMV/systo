<?php

declare(strict_types=1);

namespace Tickets\Festival\Repositories;

use App\Models\Festival\TypesOfPaymentModel;
use Illuminate\Support\Facades\Log;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Festival\Response\TypesOfPaymentDto;

class InMemoryMySqlTypesOfPayment implements TypesOfPaymentInterface
{
    public function __construct(
        private TypesOfPaymentModel $model
    )
    {
    }


    public function getList(bool $isAdmin = false, ?Uuid $ticketTypeId = null): array
    {
        $result = [];
        if (!$isAdmin) {
            $typesOfPayments = $this->model::where('active', '=', true);
        } else {
            $typesOfPayments = $this->model;
        }
        if ($ticketTypeId && $this->model::where('ticket_type_id', '=', $ticketTypeId->value())->exists()) {
            $typesOfPayments->where('ticket_type_id', '=', $ticketTypeId->value());
        }

        $typesOfPayments = $typesOfPayments->orderBy('sort')
            ->get();

        foreach ($typesOfPayments as $item) {
            $result[] = TypesOfPaymentDto::fromState($item->toArray());
        }

        return $result;
    }
}
