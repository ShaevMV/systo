<?php

declare(strict_types=1);

namespace App\Http\Controllers\TicketsOrder;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderTicketsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use JsonException;
use Throwable;
use Tickets\Ordering\OrderTicket\Application\Create\CreateOrder;
use Tickets\Ordering\OrderTicket\Application\GetOrderTicket\GetOrder;
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
        private GetOrder $getOrder,
    ) {
    }

    /**
     * Создать заказ
     *
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
                        'user_id' => $this->accountApplication->creatingOrGetAccountId($createOrderTicketsRequest->email)->value(),
                        'price' => $priceDto->getTotalPrice(),
                        'discount' => $priceDto->getDiscount(),
                        'status' => Status::NEW,
                    ]
                )
            );
            $this->createOrder->creating($orderTicketDto, $createOrderTicketsRequest->email);

            return response()->json([
                'success' => true,
                'massage' => 'Мы удачно зарегистрировали ваш заказ скоро мы его проверим и вы получите свои билеты! <br/>
              Так же мы создали нового пользователя и отправили вам на почту данные для авторизации',
            ]);

        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'massage' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Получить список заказов от пользователя
     * @throws JsonException
     */
    public function getUserList(): JsonResponse
    {
        /** @var string $id */
        $id = Auth::id();

        return response()->json(
            [
                'list' => $this->getOrder->listByUser(new Uuid($id))?->toArray() ?? []
            ]);
    }

    public function getList(): JsonResponse
    {

    }

    /**
     * Получить определённый заказ
     *
     * @throws JsonException
     */
    public function getOrderItem(string $id): JsonResponse
    {
        $orderItem = $this->getOrder->getItemById(new Uuid($id));

        if (is_null($orderItem) ||
            !$orderItem->getUserId()->equals(new Uuid(Auth::id()))) {
            return response()->json([
                'errors' => ['error' => 'Заказ не найден']
            ], 404);
        }

        return response()->json($orderItem->toArray());
    }
}
