<?php

declare(strict_types=1);

namespace App\Http\Controllers\TicketsOrder;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderTicketsRequest;
use App\Http\Requests\FilterForTicketOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use JsonException;
use Throwable;
use Tickets\Order\OrderTicket\Application\Create\CreateOrder;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForAdmin\OrderFilterQuery;
use Tickets\Order\OrderTicket\Application\GetOrderList\GetOrder;
use Tickets\Order\OrderTicket\Domain\OrderTicket;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Service\PriceService;
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
            // Создание или получение пользователя по email
            $userId = $this->accountApplication->creatingOrGetAccountId($createOrderTicketsRequest->email)->value();

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
                    $priceDto->toArray(),
                    [
                        'user_id' => $userId,
                        'status' => Status::NEW,
                    ]
                )
            );

            $this->createOrder->createAndSave($orderTicketDto);

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

    /**
     * @throws JsonException
     */
    public function getList(FilterForTicketOrder $filterForTicketOrder): JsonResponse
    {
        return response()->json(
            [
                'list' => $this->getOrder->listByFilter(
                    OrderFilterQuery::fromState($filterForTicketOrder->toArray())
                    )?->toArray() ?? []
            ]);
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
