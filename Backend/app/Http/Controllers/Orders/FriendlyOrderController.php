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
use Tickets\Festival\Application\GetTicketType\GetTicketType;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Orders\Friendly\Dto\FriendlyOrderDto;
use Tickets\Orders\Shared\Facade\OrderFacade;

/**
 * Контроллер дружеских заказов.
 *
 * Маршруты:
 *   POST /api/v2/orders/friendly/create           — создать (pusher)
 *   POST /api/v2/orders/friendly/changeStatus/{id} — сменить статус (pusher,admin)
 */
final class FriendlyOrderController extends Controller
{
    public function __construct(
        private readonly OrderFacade   $facade,
        private readonly GetTicketType $getTicketType,
    ) {}

    /**
     * Создать дружеский заказ (пушер создаёт от имени гостя).
     *
     * @throws Throwable
     */
    public function create(CreateOrderTicketsRequest $request): JsonResponse
    {
        try {
            $pusherId     = new Uuid(Auth::id());
            $ticketTypeId = new Uuid($request->ticket_type_id);
            $guests       = $request->guests ?? [];
            $festivalId   = new Uuid($request->festival_id);

            $priceDto = new PriceDto(
                (int)$request->price,
                count($guests),
                0,
            );

            $tickets = array_map(
                fn(array $g) => GuestsDto::fromState($g, $festivalId->value()),
                $guests,
            );

            $dto = new FriendlyOrderDto(
                id:           Uuid::random(),
                festivalId:   $festivalId,
                pusherId:     $pusherId,
                email:        $request->email,
                ticketTypeId: $ticketTypeId,
                tickets:      $tickets,
                priceDto:     $priceDto,
                comment:      $request->comment,
            );

            $this->facade->createFriendly($dto, actorId: $pusherId);

            return response()->json([
                'success' => true,
                'message' => 'Дружеский заказ успешно создан!',
            ]);

        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /** Детали одного дружеского заказа. */
    public function getItem(string $id): JsonResponse
    {
        $item = $this->facade->getFriendlyItem(new Uuid($id));

        if ($item === null) {
            return response()->json(['success' => false, 'message' => 'Заказ не найден'], 404);
        }

        return response()->json(['success' => true, 'order' => $item->toArray()]);
    }

    /** Список дружеских заказов текущего пользователя (пушера). */
    public function getUserList(): JsonResponse
    {
        $list = $this->facade->getFriendlyUserList(new Uuid(Auth::id()));

        return response()->json([
            'success' => true,
            'list'    => array_map(fn($item) => $item->toArray(), $list),
        ]);
    }

    /** Список всех дружеских заказов (admin/pusher). */
    public function getList(Request $request): JsonResponse
    {
        $list = $this->facade->getFriendlyList(
            status:     $request->get('status'),
            festivalId: $request->get('festival_id') ? new Uuid($request->get('festival_id')) : null,
        );

        return response()->json([
            'success' => true,
            'list'    => array_map(fn($item) => $item->toArray(), $list),
        ]);
    }

    /**
     * Сменить статус дружеского заказа.
     *
     * @throws Throwable
     */
    public function changeStatus(string $id, Request $request): JsonResponse
    {
        $validator = \Validator::make($request->all(), [
            'status' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $actorId    = new Uuid(Auth::id());
            $actorEmail = Auth::user()?->email ?? '';

            $order = $this->facade->changeFriendlyStatus(
                orderId:   new Uuid($id),
                newStatus: new Status($request->get('status')),
                params:    ['email' => $actorEmail],
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
