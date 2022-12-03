<?php

declare(strict_types=1);

namespace App\Http\Controllers\TicketsOrder;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderTicketsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Tickets\Ordering\OrderTicket\Application\Create\CreateOrder;
use Tickets\Ordering\OrderTicket\Application\GetOrderTicketsList\ToGetList;
use Tickets\Ordering\OrderTicket\Dto\OrderTicketDto;
use Tickets\Ordering\OrderTicket\Service\PriceService;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\User\Account\Application\AccountApplication;

class OrderTickets extends Controller
{
    public function __construct(
        private CreateOrder $createOrder,
        private AccountApplication $accountApplication,
        private PriceService $priceService,
        private ToGetList $toGetList,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function create(CreateOrderTicketsRequest $createOrderTicketsRequest): JsonResponse
    {
        try {
            // Получение цены
            $priceDto = $this->priceService->getPriceDto(
                new Uuid($createOrderTicketsRequest->ticket_type_id),
                count($createOrderTicketsRequest->guests),
                $createOrderTicketsRequest->promo_code
            );
            // Создание заказа
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
            $this->createOrder->creating($orderTicketDto, $createOrderTicketsRequest->email);

            return response()->json([
                'success' => true,
                'massage' => '',
            ]);

        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'massage' => $exception->getMessage(),
            ]);
        }
    }

    public function getUserList(): array
    {
        /** @var string $id */
        $id = Auth::id();

        return $this->toGetList->byUser(new Uuid($id))->toArray();
    }
}
