<?php

declare(strict_types=1);

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderTicketsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Order\OrderTicket\Service\PriceService;
use Tickets\Orders\Live\Dto\LiveOrderDto;
use Tickets\Orders\Shared\Facade\OrderFacade;
use Tickets\Ticket\Live\Service\CheckLiveTicketService;
use Tickets\User\Account\Application\AccountApplication;
use Tickets\User\Account\Dto\AccountDto;

/**
 * Контроллер живых заказов.
 *
 * Маршруты:
 *   POST /api/v2/orders/live/create            — создать (seller,admin)
 *   POST /api/v2/orders/live/changeStatus/{id}  — сменить статус (seller,admin)
 */
final class LiveOrderController extends Controller
{
    public function __construct(
        private readonly OrderFacade        $facade,
        private readonly AccountApplication $accountApplication,
        private readonly PriceService       $priceService,
    ) {}

    /**
     * Создать живой заказ.
     *
     * @throws Throwable
     */
    public function create(CreateOrderTicketsRequest $request): JsonResponse
    {
        try {
            $userId = new Uuid($this->accountApplication->creatingOrGetAccountId(
                AccountDto::fromState($request->toArray())
            )->value());

            $ticketTypeId = new Uuid($request->ticket_type_id);
            $festivalId   = new Uuid($request->festival_id);
            $guests       = $request->guests ?? [];

            $priceDto = $this->priceService->getPriceDto($ticketTypeId, count($guests));

            $tickets = array_map(
                fn(array $g) => GuestsDto::fromState($g, $festivalId->value()),
                $guests,
            );

            $dto = new LiveOrderDto(
                id:               Uuid::random(),
                festivalId:       $festivalId,
                userId:           $userId,
                email:            $request->email,
                phone:            $request->phone,
                typesOfPaymentId: new Uuid($request->types_of_payment_id),
                ticketTypeId:     $ticketTypeId,
                tickets:          $tickets,
                priceDto:         $priceDto,
            );

            $this->facade->createLive($dto, actorId: $userId);

            return response()->json([
                'success' => true,
                'message' => 'Живой заказ создан. Ожидает подтверждения оплаты.',
            ]);

        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /** Детали одного живого заказа. */
    public function getItem(string $id): JsonResponse
    {
        $order = $this->facade->findLive(new Uuid($id));

        if ($order === null) {
            return response()->json(['success' => false, 'message' => 'Заказ не найден'], 404);
        }

        $currentUserId = new Uuid(Auth::id());
        $role          = Auth::user()->role ?? 'guest';

        if (!$order->canViewItem($role, $currentUserId)) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $item = $this->facade->getLiveItem(new Uuid($id));

        return response()->json(['success' => true, 'order' => $item->toArray()]);
    }

    /** Список живых заказов текущего пользователя. */
    public function getUserList(): JsonResponse
    {
        $list = $this->facade->getLiveUserList(new Uuid(Auth::id()));

        return response()->json([
            'success' => true,
            'list'    => array_map(fn($item) => $item->toArray(), $list),
        ]);
    }

    /** Список всех живых заказов (admin/seller). */
    public function getList(Request $request): JsonResponse
    {
        $list = $this->facade->getLiveList(
            status:     $request->get('status'),
            festivalId: $request->get('festival_id') ? new Uuid($request->get('festival_id')) : null,
        );

        return response()->json([
            'success' => true,
            'list'    => array_map(fn($item) => $item->toArray(), $list),
        ]);
    }

    /**
     * Сменить статус живого заказа.
     *
     * Params по типу перехода:
     * - PAID_FOR_LIVE      → email + kilter автоматически из БД
     * - LIVE_TICKET_ISSUED → liveNumbers: [ticketId => number]
     * - CANCEL             → email
     * - CANCEL_FOR_LIVE    → нет дополнительных params
     * - DIFFICULTIES_AROSE → email + comment
     *
     * @throws Throwable
     */
    public function changeStatus(
        string                 $id,
        Request                $request,
        CheckLiveTicketService $checkLiveTicketService,
    ): JsonResponse {
        $rules = [
            'status' => 'required|string',
        ];
        if ($request->get('status') === Status::DIFFICULTIES_AROSE) {
            $rules['comment'] = 'required|string';
        }
        if ($request->get('status') === Status::LIVE_TICKET_ISSUED) {
            $rules['liveNumbers'] = 'required|array';
        }

        $validator = \Validator::make($request->all(), $rules, [
            'comment.required'     => 'Комментарий обязателен для статуса «Возникли трудности»',
            'liveNumbers.required' => 'Необходимо передать номера живых билетов',
        ]);

        $validator->after(function ($v) use ($request, $checkLiveTicketService) {
            if ($request->get('status') === Status::LIVE_TICKET_ISSUED) {
                foreach ((array)$request->get('liveNumbers', []) as $liveNumber) {
                    if ($checkLiveTicketService->checkLiveNumber((int)$liveNumber)) {
                        $v->errors()->add('liveNumbers', "Номер $liveNumber уже выдан");
                        break;
                    }
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $newStatus = new Status($request->get('status'));
        $role      = Auth::user()->role ?? 'guest';

        $order = $this->facade->findLive(new Uuid($id));
        if ($order === null) {
            return response()->json(['success' => false, 'message' => 'Заказ не найден'], 404);
        }

        if (!$order->canChangeStatus($role, $newStatus)) {
            return response()->json(['success' => false, 'message' => 'Недостаточно прав для смены статуса'], 403);
        }

        try {
            $actorId    = new Uuid(Auth::id());
            $actorEmail = Auth::user()?->email ?? '';

            $params = match ($request->get('status')) {
                Status::PAID_FOR_LIVE      => ['email' => $actorEmail, 'kilter' => 0],
                Status::LIVE_TICKET_ISSUED => ['liveNumbers' => $request->get('liveNumbers', [])],
                Status::CANCEL             => ['email' => $actorEmail],
                Status::CANCEL_FOR_LIVE    => [],
                Status::DIFFICULTIES_AROSE => ['email' => $actorEmail, 'comment' => $request->get('comment')],
                default                    => [],
            };

            $order = $this->facade->changeLiveStatus(
                orderId:   new Uuid($id),
                newStatus: $newStatus,
                params:    $params,
                actorId:   $actorId,
            );

            return response()->json([
                'success' => true,
                'status'  => [
                    'name'                  => (string)$order->getStatus(),
                    'humanStatus'           => $order->getStatus()->getHumanStatus(),
                    'listCorrectNextStatus' => $order->getAvailableTransitions(),
                ],
            ]);

        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
