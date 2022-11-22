<?php

declare(strict_types = 1);

namespace App\Http\Controllers\TicketsOrder;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
    public function create(): array
    {
        dd(123);

        $userId = $this->accountApplication->creatingOrGetId($request->input('email'));

        $orderTicketDto = OrderTicketDto::fromState($request->toArray());

        $this->createOrder->creating();
    }
}
