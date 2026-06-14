<?php

declare(strict_types=1);

namespace App\Http\Controllers\QrOrder;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\History\Dto\DomainHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\QrOrder\Application\GetList\QrOrderGetListQuery;
use Tickets\QrOrder\Application\QrOrderApplication;
use Tickets\QrOrder\Dto\QrOrderDto;
use Tickets\QrOrder\Responses\QrOrderItemForListResponse;

/**
 * API приёма заказов от витрины qr.spaceofjoy.ru.
 *
 * API №1 — создание заказа: принимает расширенный JSON-контракт и сохраняет его в qr_orders
 * (payload as-is + проекция для фильтров). id заказа qr == id заказа org.
 *
 * Аутентификация канала: create/changeStatus закрыты Sanctum-токеном со scope qr:ingest
 * (auth:sanctum + abilities:qr:ingest, см. routes/qrOrder.php). getItem — JWT + admin (ПДн).
 */
class QrOrderController extends Controller
{
    public function create(
        Request $request,
        QrOrderApplication $application,
    ): JsonResponse {
        try {
            $dto = QrOrderDto::fromQrContract($request->toArray());
            $application->create($dto);

            return response()->json([
                'success' => true,
                'order_id' => $dto->getId()->value(),
                'message' => 'Заказ принят',
            ]);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Список принятых qr-заказов для админки org (read-only): фильтры + пагинация + total.
     * Заказы НЕ редактируются отсюда — создание/смена статуса идут S2S-каналом от витрины qr.
     */
    public function getList(
        Request $request,
        QrOrderApplication $application,
    ): JsonResponse {
        $data = $request->toArray();

        // OrderType кидает InvalidArgumentException на чужих значениях (не asc/desc/none) —
        // оборачиваем, чтобы кривой orderBy не ронял запрос (как в TicketTypePriceController).
        try {
            $orderBy = Order::fromState($data['orderBy'] ?? []);
        } catch (InvalidArgumentException) {
            $orderBy = Order::none();
        }

        $page = max(1, (int) ($data['page'] ?? 1));
        $perPage = (int) ($data['perPage'] ?? 20);
        $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 20;

        $response = $application->getList(
            new QrOrderGetListQuery($data['filter'] ?? [], $orderBy, $page, $perPage),
        );

        return response()->json([
            'success' => true,
            'list' => $response->getCollection()
                ->map(fn (QrOrderItemForListResponse $dto) => $dto->toArray())
                ->values()
                ->all(),
            'totalNumber' => ['totalCount' => $response->getTotalCount()],
        ]);
    }

    public function getItem(
        string $id,
        QrOrderApplication $application,
    ): JsonResponse {
        $item = $application->getItem(new Uuid($id));

        if ($item === null) {
            return response()->json(['success' => false, 'message' => 'Заказ не найден'], 404);
        }

        return response()->json(['success' => true, 'item' => $item->toArray()]);
    }

    /**
     * API №2 — смена статуса принятого заказа.
     * Шаг 2a: обновляет статус. Шаг 2b: при «оплачен» запустит выдачу билетов.
     */
    public function changeStatus(
        string $id,
        Request $request,
        QrOrderApplication $application,
    ): JsonResponse {
        $status = (string) $request->input('status', '');
        if ($status === '') {
            return response()->json(['success' => false, 'message' => 'Не передан status'], 422);
        }

        if (! $application->changeStatus(new Uuid($id), $status)) {
            return response()->json(['success' => false, 'message' => 'Заказ не найден'], 404);
        }

        return response()->json([
            'success' => true,
            'item' => $application->getItem(new Uuid($id))?->toArray(),
            'message' => 'Статус обновлён',
        ]);
    }

    /**
     * История заказа qr (таймлайн для админа org): created → status_changed → issued.
     * Actor — ActorType::QR (S2S-канал, не человек).
     */
    public function getHistory(
        string $id,
        HistoryRepositoryInterface $history,
    ): JsonResponse {
        $items = array_map(
            static fn (DomainHistoryDto $h): array => [
                'event_name' => $h->eventName,
                'aggregate_type' => $h->aggregateType,
                'payload' => $h->payload,
                'actor_type' => $h->actorType,
                'occurred_at' => $h->occurredAt->toIso8601String(),
            ],
            $history->getByAggregateId($id),
        );

        return response()->json(['success' => true, 'history' => $items]);
    }
}
