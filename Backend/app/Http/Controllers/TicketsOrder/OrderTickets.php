<?php

declare(strict_types=1);

namespace App\Http\Controllers\TicketsOrder;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderTicketsRequest;
use App\Http\Requests\FilterForTicketOrder;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Nette\Utils\JsonException;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Shared\Domain\Criteria\Order;
use Tickets\Festival\Application\GetTicketType\GetTicketType;
use Tickets\History\Application\GetHistory\GetOrderHistory;
use Tickets\TicketType\Application\GetList\TicketTypeGetListFilter;
use Tickets\TicketType\Application\GetList\TicketTypeGetListQuery;
use Tickets\TicketType\Application\TicketTypeApplication;
use Tickets\Order\OrderTicket\Application\AddComment\AddComment;
use Tickets\Order\OrderTicket\Application\ChangeOrderPrice\ChangeOrderPrice;
use Tickets\Order\OrderTicket\Application\ChangeStatus\ChangeStatus;
use Tickets\Order\OrderTicket\Application\Create\CreateOrder;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForAdmin\OrderFilterQuery;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForFriendly\OrderFilterQuery as OrderFilterQueryForFriendly;
use Tickets\Order\OrderTicket\Application\GetOrderList\GetOrder;
use Tickets\Order\OrderTicket\Application\TotalNumber\TotalNumber;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Order\OrderTicket\Responses\ListResponse;
use Tickets\Order\OrderTicket\Service\PriceService;
use Tickets\Ticket\CreateTickets\Application\ChangeTicket\ChangeTicket;
use Tickets\Ticket\CreateTickets\Application\TicketApplication;
use Tickets\Ticket\Live\Service\CheckLiveTicketService;
use Tickets\User\Account\Application\AccountApplication;
use Tickets\User\Account\Dto\AccountDto;
use Tickets\User\Account\Helpers\AccountRoleHelper;

class OrderTickets extends Controller
{
    public function __construct(
        private CreateOrder           $createOrder,
        private GetTicketType         $getTicketType,
        private AccountApplication    $accountApplication,
        private PriceService          $priceService,
        private GetOrder              $getOrder,
        private TotalNumber           $totalNumber,
        private ChangeStatus          $chanceStatus,
        private ChangeOrderPrice      $changeOrderPrice,
        private TicketApplication     $ticketApplication,
        private AddComment            $addComment,
        private ChangeTicket          $changeTicket,
        private GetOrderHistory       $getOrderHistory,
        private TicketTypeApplication $ticketTypeApplication,
       // private Billing            $billing,
    )
    {
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
            $userId = new Uuid($this->accountApplication->creatingOrGetAccountId(
                AccountDto::fromState($createOrderTicketsRequest->toArray())
            )->value());
            $ticketTypeId = new Uuid($createOrderTicketsRequest->ticket_type_id);
            $guests = $createOrderTicketsRequest->guests;
            if ($createOrderTicketsRequest->name) {
                array_unshift($guests, [
                    'value' => $createOrderTicketsRequest->name,
                    'email' => $createOrderTicketsRequest->email,
                ]);
            }

            // Получение цены
            $priceDto = $this->priceService->getPriceDto(
                $ticketTypeId,
                count($guests),
                $createOrderTicketsRequest->promo_code
            );

            $ticketType = $this->getTicketType->getTicketsTypeByUuid($ticketTypeId);

            $data = $createOrderTicketsRequest->toArray();
            $data['guests'] = $guests;

            $orderTicketDto = OrderTicketDto::fromState(
                $data,
                $userId,
                $priceDto,
                $ticketType->isLiveTicket(),
            );

            if ($createOrderTicketsRequest->invite !== 'undefined' &&
                $createOrderTicketsRequest->invite !== null) {
                $orderTicketDto->setInviteLink(new Uuid($createOrderTicketsRequest->invite));
            }

            $this->createOrder->createAndSave($orderTicketDto);

            // Добавления комментария
            if ($createOrderTicketsRequest->comment) {
                $this->addComment->send(
                    $orderTicketDto->getId(),
                    $userId,
                    $createOrderTicketsRequest->comment
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Мы удачно зарегистрировали ваш заказ скоро мы его проверим и вы получите свои билеты! <br/>
              Так же мы создали нового пользователя и отправили вам на почту данные для авторизации',
            ]);

        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'link' => $exception->getLine(),
                'file' => $exception->getFile(),
            ]);
        }
    }


    /**
     * Создать заказ
     *
     * @throws Throwable
     */
    public function createFriendly(
        CreateOrderTicketsRequest $createOrderTicketsRequest,
        CheckLiveTicketService    $checkLiveTicketService
    ): JsonResponse
    {
        try {
            // Создание или получение пользователя по email
            $userId = new Uuid(Auth::id());
            $ticketTypeId = new Uuid($createOrderTicketsRequest->ticket_type_id);
            $guests = $createOrderTicketsRequest->guests;
            $priceDto = new PriceDto(
                (int)$createOrderTicketsRequest->price,
                count($guests),
                0
            );

            $ticketType = $this->getTicketType->getTicketsTypeByUuid($ticketTypeId);

            $data = $createOrderTicketsRequest->toArray();
            $data['guests'] = $guests;
            if ($ticketType->isLiveTicket()) {
                foreach ($guests as $guest) {
                    if ($checkLiveTicketService->checkLiveNumber($guest['number'])) {
                        throw new \DomainException("Номер билета " . $guest['number'] . " уже выдан ");
                    }
                }
            }
            $data['status'] = $ticketType->isLiveTicket() ? Status::LIVE_TICKET_ISSUED : Status::PAID;
            $data['types_of_payment_id'] = '613d6bb9-a3a0-480e-ade8-05625fc19544';
            Log::info('Создание заказа ', $data);
            $orderTicketDto = OrderTicketDto::fromState(
                $data,
                $userId,
                $priceDto,
                $ticketType->isLiveTicket(),
                $userId
            );

            $this->createOrder->createAndSaveForFriendly($orderTicketDto);

            // Добавления комментария
            if ($createOrderTicketsRequest->comment) {
                $this->addComment->send(
                    $orderTicketDto->getId(),
                    $userId,
                    $createOrderTicketsRequest->comment
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Мы удачно зарегистрировали ваш заказ скоро мы его проверим и вы получите свои билеты!',
            ]);

        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'link' => $exception->getLine(),
                'file' => $exception->getFile(),
            ]);
        }
    }


    /**
     * Создать заказ-список куратора — статус new_for_list, ожидает модерации администратора
     */
    public function createList(Request $request): JsonResponse
    {
        try {
            $curatorId = new Uuid(Auth::id());
            $festivalId = $request->festival_id;
            $locationId = new Uuid($request->location_id);
            $guests = $request->guests;

            // Автоматически находим тип билета "Список" для данного фестиваля
            $listTypes = $this->ticketTypeApplication->getList(
                new TicketTypeGetListQuery(
                    TicketTypeGetListFilter::fromState([
                        'is_list_ticket' => 'true',
                        'festival_id'    => $festivalId,
                    ]),
                    Order::fromState([]),
                )
            )->getCollection();

            if ($listTypes->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Тип билета "Список" не настроен для данного фестиваля',
                ], 422);
            }

            /** @var \Tickets\TicketType\Dto\TicketTypeDto $ticketType */
            $ticketType = $listTypes->first();

            $priceDto = new PriceDto(
                (int)($request->price ?? 0),
                count($guests),
                0
            );

            $data = $request->toArray();
            $data['guests'] = $guests;
            $data['ticket_type_id'] = $ticketType->getId()->value();
            $data['types_of_payment_id'] = '613d6bb9-a3a0-480e-ade8-05625fc19544';
            $data['status'] = Status::NEW_FOR_LIST;

            $orderTicketDto = OrderTicketDto::fromState(
                $data,
                $curatorId,
                $priceDto,
                curatorId: $curatorId,
                locationId: $locationId,
            );

            $this->createOrder->createAndSaveForCurator($orderTicketDto);

            if ($request->comment) {
                $this->addComment->send(
                    $orderTicketDto->getId(),
                    $curatorId,
                    $request->comment
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Заказ куратора создан и ожидает модерации администратора',
            ]);

        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'link'    => $exception->getLine(),
                'file'    => $exception->getFile(),
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
        /** @var User $user */
        $user = Auth::user();

        $listResponse = $this->getOrder->listByFilter(
            OrderFilterQuery::fromState(
                $filterForTicketOrder->toArray(),
                $user->role === AccountRoleHelper::admin ? null : new Uuid(Auth::id())
            )
        ) ?? new ListResponse();

        return response()->json(
            [
                'list' => $listResponse->toArray(),
                'totalNumber' => $this->totalNumber->getTotalNumber($listResponse)->toArray()
            ]);
    }

    public function getFriendlyList(FilterForTicketOrder $filterForTicketOrder): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $listResponse = $this->getOrder->listByFilterForFriendly(
            OrderFilterQueryForFriendly::fromState(
                $filterForTicketOrder->toArray(),
                $user->role === AccountRoleHelper::admin ? null : new Uuid(Auth::id()),
            )
        ) ?? new ListResponse();

        return response()->json(
            [
                'list' => $listResponse->toArray(),
                'totalNumber' => $this->totalNumber->getTotalNumber($listResponse)->toArray()
            ]);
    }


    /**
     * Получить определённый заказ
     *
     * @throws JsonException
     */
    public function getOrderItem(string $id): JsonResponse
    {
        $orderUuid = new Uuid($id);
        $orderItem = $this->getOrder->getItemById($orderUuid);
        /** @var User $user */
        $user = Auth::user();
        if (is_null($orderItem) ||
            (!$orderItem->getUserId()->equals(new Uuid(Auth::id()))
                && !($user->role === AccountRoleHelper::admin))
        ) {
            return response()->json([
                'errors' => ['error' => 'Заказ не найден']
            ], 404);
        }

        return response()->json([
            'order' => $orderItem->toArray()
        ]);
    }

    /**
     * Сменить статус заказа
     *
     * @throws Throwable
     */
    public function toChangeStatus(
        string                 $id,
        Request                $request,
        CheckLiveTicketService $checkLiveTicketService,
    ): JsonResponse
    {
        // Базовые правила валидации
        $rules = [];
        $messages = [
            '*.required' => 'Поле обязательно для ввода',
        ];

        // Добавляем правила в зависимости от статуса
        if (in_array($request->get('status'), [
            Status::DIFFICULTIES_AROSE,
        ])) {
            $rules['comment'] = 'required|string';
        }

        if (in_array($request->get('status'), [
            Status::LIVE_TICKET_ISSUED,
        ])) {
            $rules['liveList'] = 'required|array';
        }

        // Создаем валидатор
        $validator = \Validator::make($request->all(), $rules, $messages);

        // Добавляем кастомную проверку для liveList
        $validator->after(function ($validator) use ($request, $checkLiveTicketService) {
            // Проверяем только если статус LIVE_TICKET_ISSUED и liveList передан
            if (in_array($request->get('status'), [Status::LIVE_TICKET_ISSUED]) &&
                $request->has('liveList')) {

                foreach ($request->get('liveList', []) as $item) {
                    if ($checkLiveTicketService->checkLiveNumber((int)$item)) {
                        $validator->errors()->add(
                            'liveList',
                            "Номер $item выдан ранее"
                        );
                        break; // Можно остановиться на первой ошибке
                    }
                }
            }
        });

        // Проверяем результат валидации
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422); // 422 Unprocessable Entity - стандартный код для ошибок валидации
        }

        // Если валидация прошла успешно, выполняем основной код
        $status = new Status($request->get('status'));

        $this->chanceStatus->change(
            new Uuid($id),
            $status,
            new Uuid(Auth::id()),
            $request->get('comment', null),
            liveList: $request->get('liveList', [])
        );

        // Получаем обновлённый заказ для возврата полного объекта
        $updatedOrder = $this->getOrder->getItemById(new Uuid($id));

        return response()->json([
            'success' => true,
            'status' => [
                'name' => $request->get('status'),
                'humanStatus' => $status->getHumanStatus(),
                'listCorrectNextStatus' => $status->getListNextStatus(),
            ],
            'order' => $updatedOrder?->toArray()
        ]);
    }

    /**
     * Изменить цену заказа (только для admin)
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function changePrice(
        string  $id,
        Request $request,
    ): JsonResponse
    {
        // Валидация
        $rules = [
            'price' => 'required|numeric|gt:0',
        ];
        $messages = [
            'price.required' => 'Цена обязательна',
            'price.numeric' => 'Цена должна быть числом',
            'price.gt' => 'Цена должна быть больше 0',
        ];

        $validator = \Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $newPrice = (float) $request->get('price');

        $this->changeOrderPrice->change(
            new Uuid($id),
            $newPrice,
            new Uuid(Auth::id())
        );

        return response()->json([
            'success' => true,
            'price' => $newPrice,
        ]);
    }

    public function changeTicket(
        string $id,
        Request $request,
    ): JsonResponse
    {
        $rules = [
            'email' => 'required|array',
            'value' => 'required|array',
            'email.*' => 'required|email',
            'value.*' => 'required'
        ];

        $messages = [
            // Общие ошибки для полей верхнего уровня
            'email.required' => 'Поле "email" обязательно.',
            'value.required' => 'Поле "ФИО" обязательно.',
            'email.array'    => 'Поле "email" должно быть массивом.',
            'value.array'    => 'Поле "ФИО" должно быть массивом.',

            // Ошибки для каждого элемента массива email.*
            'email.*.required' => 'Email не может быть пустым.',
            'email.*.email'    => 'Введите корректный email адрес.',

            // Ошибки для каждого элемента массива value.*
            'value.*.required' => 'ФИО не может быть пустым.',
        ];

        $validator = \Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $this->changeTicket->change(
            new Uuid($id),
            $request->input('value', []),
            $request->input('email', []),
            Auth::id(),
        );

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * История изменений заказа (только для администратора)
     */
    public function getHistory(string $id): JsonResponse
    {
        $history = $this->getOrderHistory->getByOrderId($id);

        return response()->json([
            'success' => true,
            'history' => array_map(fn($item) => [
                'event_name'     => $item->eventName,
                'aggregate_type' => $item->aggregateType,
                'payload'        => $item->payload,
                'actor_id'       => $item->actorId,
                'actor_type'     => $item->actorType,
                'occurred_at'    => $item->occurredAt->toIso8601String(),
            ], $history),
        ]);
    }

    /**
     * Получить список билетов в PDF
     *
     * @param string $id
     * @return JsonResponse
     */
    public function getUrlListForPdf(string $id): JsonResponse
    {
        try {
            $urlsTicketPdfResponse = $this->ticketApplication->getPdfList(new Uuid($id));

            return response()->json([
                'success' => true,
                'listUrl' => $urlsTicketPdfResponse->getUrls()
            ]);
        } catch (Throwable $throwable) {
            return response()->json([
                'success' => false,
                'message' => $throwable->getMessage()
            ], 422);
        }
    }
}
