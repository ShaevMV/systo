<?php

declare(strict_types=1);

namespace App\Http\Controllers\EmailDelivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Tickets\EmailDelivery\Application\EmailDeliveryApplication;
use Tickets\EmailDelivery\Application\GetList\EmailMessageGetListQuery;
use Tickets\EmailDelivery\Responses\EmailMessageItemForListResponse;
use Tickets\History\Dto\DomainHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;

/**
 * Админ-экран «Доставка писем» (Ф2, admin-only, содержит ПДн): список + деталь + повтор.
 * Отправку выполняет MailDispatcher/SendEmailJob; отсюда — только просмотр и повторная отправка.
 */
class EmailDeliveryController extends Controller
{
    public function getList(Request $request, EmailDeliveryApplication $application): JsonResponse
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
            new EmailMessageGetListQuery($data['filter'] ?? [], $orderBy, $page, $perPage),
        );

        return response()->json([
            'success' => true,
            'list' => $response->getCollection()
                ->map(fn (EmailMessageItemForListResponse $dto) => $dto->toArray())
                ->values()
                ->all(),
            'totalNumber' => ['totalCount' => $response->getTotalCount()],
        ]);
    }

    public function getItem(
        string $id,
        EmailDeliveryApplication $application,
        HistoryRepositoryInterface $history,
    ): JsonResponse {
        $item = $application->getItem(new Uuid($id));

        if ($item === null) {
            return response()->json(['success' => false, 'message' => 'Письмо не найдено'], 404);
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

    public function resend(string $id, EmailDeliveryApplication $application): JsonResponse
    {
        $authorId = ($auth = Auth::id()) instanceof Uuid ? $auth->value() : ($auth === null ? null : (string) $auth);

        if (! $application->resend(new Uuid($id), $authorId)) {
            return response()->json(['success' => false, 'message' => 'Письмо не найдено'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Письмо поставлено на повторную отправку']);
    }
}
