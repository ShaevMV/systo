<?php

declare(strict_types = 1);

namespace Tickets\Ordering\OrderTicket\Repositories;

use App\Models\Tickets\Ordering\OrderTicket;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;
use Tickets\Ordering\OrderTicket\Dto\OrderTicketDto;

final class InMemoryMySqlOrderTicket implements OrderTicketInterface
{
    public function __construct(
        private OrderTicket $model,
    ){
    }


    /**
     * @throws Throwable
     */
    public function create(OrderTicketDto $orderTicketDto): bool
    {
        DB::beginTransaction();
        $data = $orderTicketDto->toArray();
        try {
            $this->model::create($orderTicketDto->toArray());
            DB::commit();
            return true;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }
}
