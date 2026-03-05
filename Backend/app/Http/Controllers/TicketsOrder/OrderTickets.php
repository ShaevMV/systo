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
use Nette\Utils\JsonException;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\Festival\Application\GetTicketType\GetTicketType;
use Tickets\Order\OrderTicket\Application\AddComment\AddComment;
use Tickets\Order\OrderTicket\Application\ChanceStatus\ChanceStatus;
use Tickets\Order\OrderTicket\Application\Create\CreateOrder;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForAdmin\OrderFilterQuery;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForFriendly\OrderFilterQuery as OrderFilterQueryForFriendly;
use Tickets\Order\OrderTicket\Application\GetOrderList\GetOrder;
use Tickets\Order\OrderTicket\Application\TotalNumber\TotalNumber;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Order\OrderTicket\Responses\ListResponse;
use Tickets\Order\OrderTicket\Service\PriceService;
use Tickets\Ticket\CreateTickets\Application\TicketApplication;
use Tickets\Ticket\Live\Service\CheckLiveTicketService;
use Tickets\User\Account\Application\AccountApplication;
use Tickets\User\Account\Dto\AccountDto;
use Tickets\User\Account\Helpers\AccountRoleHelper;

class OrderTickets extends Controller
{
    public function __construct(
        private CreateOrder        $createOrder,
        private GetTicketType      $getTicketType,
        private AccountApplication $accountApplication,
        private PriceService       $priceService,
        private GetOrder           $getOrder,
        private TotalNumber        $totalNumber,
        private ChanceStatus       $chanceStatus,
        private TicketApplication  $ticketApplication,
        private AddComment         $addComment,
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
            $data['status'] = $ticketType->isLiveTicket() ? Status::PAID_FOR_LIVE : Status::PAID;

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
    public function toChanceStatus(
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

        $this->chanceStatus->chance(
            new Uuid($id),
            $status,
            new Uuid(Auth::id()),
            $request->get('comment', null),
            liveList: $request->get('liveList', [])
        );

        return response()->json([
            'success' => true,
            'status' => [
                'name' => $request->get('status'),
                'humanStatus' => $status->getHumanStatus(),
                'listCorrectNextStatus' => $status->getListNextStatus(),
            ]
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
