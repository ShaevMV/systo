<?php

declare(strict_types=1);

namespace App\Http\Controllers\BazaDelivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Tickets\BazaDelivery\Application\BazaDeliveryApplication;
use Tickets\BazaDelivery\Application\GetList\BazaDeliveryGetListQuery;
use Tickets\BazaDelivery\Responses\BazaDeliveryItemForListResponse;
use Tickets\History\Dto\DomainHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;

/**
 * Админ-экран «Доставка в baza» (AF-4, admin-only, содержит ПДн): список + деталь + повтор + статистика.
 * Постановку в очередь выполняет BazaDeliveryDispatcher/DeliverTicketToBazaJob; здесь — только
 * просмотр и ручной повтор застрявшей доставки.
 */
class BazaDeliveryController extends Controller
{
    public function getList(Request $request, BazaDeliveryApplication $application): JsonResponse
    {
        $data = $request->toArray();

        try {
            $orderBy = Order::fromState($data['orderBy'] ?? []);
        } catch (InvalidArgumentException) {
            $orderBy = Order::none();
        }

        $page = max(1, (int) ($data['page'] ?? 1));
        $perPage = (int) ($data['perPage'] ?? 20);
        $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 20;

        $response = $application->getList(
            new BazaDeliveryGetListQuery($data['filter'] ?? [], $orderBy, $page, $perPage),
        );

        return response()->json([
            'success' => true,
            'list' => $response->getCollection()
                ->map(fn (BazaDeliveryItemForListResponse $dto) => $dto->toArray())
                ->values()
                ->all(),
            'totalNumber' => ['totalCount' => $response->getTotalCount()],
        ]);
    }

    public function getItem(
        string $id,
        BazaDeliveryApplication $application,
        HistoryRepositoryInterface $history,
    ): JsonResponse {
        $item = $application->getItem(new Uuid($id));

        if ($item === null) {
            return response()->json(['success' => false, 'message' => 'Доставка не найдена'], 404);
        }

        $timeline = array_map(
            static fn (DomainHistoryDto $h): array => [
                'event_name' => $h->eventName,
                'payload' => $h->payload,
                'actor_type' => $h->actorType,
                'occurred_at' => $h->occurredAt->toIso8601String(),
            ],
            $history->getByAggregateId($id),
        );

        return response()->json([
            'success' => true,
            'item' => $item->toArray(),
            'history' => $timeline,
        ]);
    }

    public function resend(string $id, BazaDeliveryApplication $application): JsonResponse
    {
        $authorId = ($auth = Auth::id()) instanceof Uuid ? $auth->value() : ($auth === null ? null : (string) $auth);

        if (! $application->resend(new Uuid($id), $authorId)) {
            return response()->json(['success' => false, 'message' => 'Доставка не найдена'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Доставка поставлена на повторную попытку']);
    }

    /** Счётчики доставок по статусам (для дашборд-виджета «застрявшие билеты»). festival_id — опционально. */
    public function getStats(Request $request, BazaDeliveryApplication $application): JsonResponse
    {
        $data = $request->toArray();

        $festivalId = null;
        if (! empty($data['festival_id'])) {
            try {
                $festivalId = new Uuid((string) $data['festival_id']);
            } catch (InvalidArgumentException) {
                $festivalId = null;
            }
        }

        return response()->json([
            'success' => true,
            'stats' => $application->getStats($festivalId),
        ]);
    }
}
