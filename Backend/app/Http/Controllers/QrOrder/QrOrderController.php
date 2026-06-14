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
 * TODO(безопасность): эндпоинт create — server-to-server от qr. Сейчас публичный (как order/create).
 * Механизм аутентификации канала (service-token / подпись) — отдельное решение владельца.
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
}
