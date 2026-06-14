<?php

declare(strict_types=1);

namespace App\Http\Controllers\QrOrder;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\QrOrder\Application\QrOrderApplication;
use Tickets\QrOrder\Dto\QrOrderDto;

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
}
