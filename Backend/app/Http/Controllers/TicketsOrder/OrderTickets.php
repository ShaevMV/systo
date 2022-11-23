<?php

declare(strict_types = 1);

namespace App\Http\Controllers\TicketsOrder;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderTicketsRequest;
use Throwable;
use Tickets\Ordering\OrderTicket\Application\Create\CreateOrder;
use Tickets\Ordering\OrderTicket\Dto\OrderTicketDto;
use Tickets\Ordering\OrderTicket\Service\PriceService;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\User\Application\AccountApplication;

class OrderTickets extends Controller
{
    public function __construct(
        private CreateOrder $createOrder,
        private AccountApplication $accountApplication,
        private PriceService $priceService,
    ){
    }

    /**
     * @throws Throwable
     */
    public function create(CreateOrderTicketsRequest $createOrderTicketsRequest): array
    {
        $priceDto = $this->priceService->getPriceDto(
            new Uuid($createOrderTicketsRequest->ticket_type_id),
            count($createOrderTicketsRequest->guests),
            $createOrderTicketsRequest->promo_code
        );

        $orderTicketDto = OrderTicketDto::fromState(
            array_merge(
                $createOrderTicketsRequest->toArray(),
                [
                    'user_id' => $this->accountApplication->creatingOrGetAccount($createOrderTicketsRequest->email)->value(),
                    'price' => $priceDto->getTotalPrice(),
                    'discount' => $priceDto->getDiscount(),
                    'status' => Status::NEW,
                ]
            )
        );
        $this->createOrder->creating($orderTicketDto);

        return [];
    }
}
