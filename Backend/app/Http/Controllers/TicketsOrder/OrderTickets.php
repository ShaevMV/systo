<?php

declare(strict_types = 1);

namespace App\Http\Controllers\TicketsOrder;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderTicketsRequest;
use Throwable;
use Tickets\Ordering\InfoForOrder\Application\GetPriceByTicketType\GetPriceByTicketType;
use Tickets\Ordering\InfoForOrder\Application\SearchPromoCode\IsCorrectPromoCode;
use Tickets\Ordering\OrderTicket\Application\Create\CreateOrder;
use Tickets\Ordering\OrderTicket\Dto\OrderTicketDto;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\User\Application\AccountApplication;

class OrderTickets extends Controller
{
    public function __construct(
        private CreateOrder $createOrder,
        private AccountApplication $accountApplication,
        private GetPriceByTicketType $getPriceByTicketType,
        private IsCorrectPromoCode $isCorrectPromoCode,
    ){
    }

    /**
     * @throws Throwable
     */
    public function create(CreateOrderTicketsRequest $createOrderTicketsRequest): array
    {
        $discount = !is_null($createOrderTicketsRequest->promoCode) ? $this->isCorrectPromoCode->findPromoCode($createOrderTicketsRequest->promoCode)?->getDiscount() : null;

        $orderTicketDto = OrderTicketDto::fromState(
            array_merge(
                $createOrderTicketsRequest->toArray(),
                [
                    'user_id' => $this->accountApplication->creatingOrGetAccount($createOrderTicketsRequest->email)->value(),
                    'price' => $this->getPriceByTicketType->getPrice(new Uuid($createOrderTicketsRequest->ticket_type_id))->getPrice(),
                    'discount' => $discount ?? 0.00,
                    'status' => Status::NEW,
                ]
            )
        );
        $this->createOrder->creating($orderTicketDto);

        return [];
    }
}
