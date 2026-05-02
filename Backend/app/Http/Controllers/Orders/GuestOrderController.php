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
use Tickets\Order\OrderTicket\Service\PriceService;
use Tickets\Orders\Guest\Dto\GuestOrderDto;
use Tickets\Orders\Shared\Facade\OrderFacade;
use Tickets\Ticket\Live\Service\CheckLiveTicketService;
use Tickets\User\Account\Application\AccountApplication;
use Tickets\User\Account\Dto\AccountDto;

/**
 * Контроллер гостевых заказов.
 *
 * Маршруты:
 *   POST /api/v2/orders/guest/create         — создать заказ (публичный)
 *   POST /api/v2/orders/guest/changeStatus/{id} — сменить статус (seller,admin)
 */
final class GuestOrderController extends Controller
{
    public function __construct(
        private readonly OrderFacade        $facade,
        private readonly AccountApplication $accountApplication,
        private readonly GetTicketType      $getTicketType,
        private readonly PriceService       $priceService,
    ) {}

    /**
     * Создать гостевой заказ.
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
            $guests       = $request->guests ?? [];

            if ($request->name) {
                array_unshift($guests, [
                    'value' => $request->name,
                    'email' => $request->email,
                ]);
            }

            $priceDto     = $this->priceService->getPriceDto(
                $ticketTypeId,
                count($guests),
                $request->promo_code,
            );

            $festivalId = new Uuid($request->festival_id);

            $tickets = array_map(
                fn(array $g) => GuestsDto::fromState($g, $festivalId->value()),
                $guests,
            );

            $dto = new GuestOrderDto(
                id:               Uuid::random(),
                festivalId:       $festivalId,
                userId:           $userId,
                email:            $request->email,
                phone:            $request->phone,
                typesOfPaymentId: new Uuid($request->types_of_payment_id),
                ticketTypeId:     $ticketTypeId,
                tickets:          $tickets,
                priceDto:         $priceDto,
                status:           new Status(Status::NEW),
                promoCode:        $request->promo_code,
                inviteLink:       $request->invite ? new Uuid($request->invite) : null,
            );

            $this->facade->createGuest($dto, actorId: $userId);

            return response()->json([
                'success' => true,
                'message' => 'Заказ успешно создан. Скоро вы получите билеты на почту!',
            ]);

        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Сменить статус гостевого заказа.
     *
     * @throws Throwable
     */
    public function changeStatus(
        string                 $id,
        Request                $request,
        CheckLiveTicketService $checkLiveTicketService,
    ): JsonResponse {
        $rules = [];
        if ($request->get('status') === Status::DIFFICULTIES_AROSE) {
            $rules['comment'] = 'required|string';
        }

        $validator = \Validator::make($request->all(), $rules, [
            'comment.required' => 'Комментарий обязателен для статуса «Возникли трудности»',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $actorId   = new Uuid(Auth::id());
            $actorEmail = Auth::user()?->email ?? '';

            $order = $this->facade->changeGuestStatus(
                orderId:   new Uuid($id),
                newStatus: new Status($request->get('status')),
                params:    [
                    'email'   => $actorEmail,
                    'comment' => $request->get('comment'),
                ],
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
