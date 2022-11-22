<?php

declare(strict_types = 1);

namespace App\Http\Controllers\TicketsOrder;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderTickets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use Tickets\Ordering\OrderTicket\Application\Create\CreateOrder;
use Tickets\Ordering\OrderTicket\Dto\OrderTicketDto;
use Tickets\User\Application\AccountApplication;

class OrderTickets extends Controller
{
    public function __construct(
        private CreateOrder $createOrder,
        private AccountApplication $accountApplication
    ){
    }

    /**
     * @throws Throwable
     */
    public function create(CreateOrderTickets $createOrderTickets): array
    {
        $a = 4;

        $userId = $this->accountApplication->creatingOrGetId($createOrderTickets->email);

        return [];



        $orderTicketDto = OrderTicketDto::fromState($request->toArray());

        $this->createOrder->creating();
    }
}
