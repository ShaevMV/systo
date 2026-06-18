<?php

declare(strict_types=1);

namespace App\Http\Controllers\Festival;

use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Nette\Utils\JsonException;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\Festival\Application\GetInfoForOrder\GetInfoForOrder;
use Tickets\History\Dto\DomainHistoryDto;
use Tickets\Order\OrderTicket\Application\GetFestivalList\FestivalApplication;
use Tickets\Order\OrderTicket\Application\GetList\FestivalGetListQuery;
use Tickets\Order\OrderTicket\Dto\Festival\FestivalDto;
use Tickets\User\Account\Helpers\AccountRoleHelper;

class FestivalController extends Controller
{
    public function __construct(
        private FestivalApplication $festivalApplication,
        private GetInfoForOrder    $allInfoForOrderingTicketsSearcher,
    )
    {
    }

    /**
     * @throws JsonException
     */
    public function getFestivalList(): array
    {
        return $this->festivalApplication->getAllFestival()->toArray();
    }


    /**
     * @throws JsonException
     */
    public function getInfoForOrder(Request $request): array
    {
        if (is_null($request->get('festival_id'))) {
            throw new DomainException('Не задан идентификатор фестиваля');
        }
        $isAdmin = filter_var($request->get('is_admin', false),FILTER_VALIDATE_BOOLEAN);
        $role = Auth::user()?->role;
        $isPusher = in_array($role, [AccountRoleHelper::pusher, AccountRoleHelper::pusher_curator], true);

        return $this->allInfoForOrderingTicketsSearcher
            ->getInfoForOrderingDto(
                new Uuid($request->get('festival_id')),
                $isAdmin,
                $isPusher
            )
            ->toArray();
    }

    /**
     * @throws JsonException
     */
    public function loadByTicketType(
        string $ticketTypeId,
    ): array
    {
        return $this->allInfoForOrderingTicketsSearcher
            ->getListTypesOfPaymentDto(new Uuid($ticketTypeId))
            ->toArray();
    }


    /**
     * @throws JsonException
     */
    public function getPriceList(Request $request): array
    {
        if (is_null($request->get('festival_id'))) {
            throw new DomainException('Не задан идентификатор фестиваля');
        }

        return $this->allInfoForOrderingTicketsSearcher->getAllPrice(new Uuid($request->get('festival_id')))->toArray();
    }


    /**
     * @throws JsonException
     */
    public function getTicketTypeList(): array
    {
        return $this->allInfoForOrderingTicketsSearcher->getListTicketTypeDto(new Uuid('9d679bcf-b438-4ddb-ac04-023fa9bff4b8'))->toArray();
    }

    /**
     * Создать фестиваль (admin). Каталог фестивалей — мастер на org.
     *
     * @throws \Throwable
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'data.name' => 'required|string|max:255',
            'data.year' => 'required|integer|min:2000|max:2100',
            'data.active' => 'boolean',
        ]);

        $dto = new FestivalDto(
            Uuid::random(),
            (string) $request->input('data.name'),
            (int) $request->input('data.year'),
            (bool) $request->input('data.active', false),
        );

        $this->festivalApplication->create($dto, $this->authorId());

        return response()->json([
            'success' => true,
            'item' => $dto->toArray(),
            'message' => 'Фестиваль создан',
        ]);
    }

    /**
     * Список фестивалей с фильтрами (name/year/active) + orderBy — для админ-CRUD.
     *
     * @throws JsonException
     */
    public function getList(Request $request): JsonResponse
    {
        try {
            $orderBy = Order::fromState($request->toArray()['orderBy'] ?? []);
        } catch (Throwable) {
            // кривое значение orderBy не должно ронять публичный список
            $orderBy = Order::none();
        }

        $collection = $this->festivalApplication->getList(
            new FestivalGetListQuery(
                $request->toArray()['filter'] ?? [],
                $orderBy,
            )
        )->getCollection();

        return response()->json([
            'success' => true,
            'list' => $collection->map(fn (FestivalDto $dto) => $dto->toArray())->values()->all(),
        ]);
    }

    /**
     * @throws JsonException
     */
    public function getItem(string $id): JsonResponse
    {
        $festival = $this->festivalApplication->getItem(new Uuid($id));

        if (null === $festival) {
            return response()->json([
                'success' => false,
                'message' => 'Фестиваль не найден',
            ]);
        }

        return response()->json([
            'success' => true,
            'item' => $festival->toArray(),
        ]);
    }

    /**
     * Редактировать фестиваль (admin).
     *
     * @throws Throwable
     */
    public function edit(string $id, Request $request): JsonResponse
    {
        $request->validate([
            'data.name' => 'required|string|max:255',
            'data.year' => 'required|integer|min:2000|max:2100',
            'data.active' => 'boolean',
        ]);

        $uuid = new Uuid($id);

        if (null === $this->festivalApplication->getItem($uuid)) {
            return response()->json([
                'success' => false,
                'message' => 'Фестиваль не найден',
            ]);
        }

        $dto = new FestivalDto(
            $uuid,
            (string) $request->input('data.name'),
            (int) $request->input('data.year'),
            (bool) $request->input('data.active', false),
        );

        $this->festivalApplication->edit($uuid, $dto, $this->authorId());

        return response()->json([
            'success' => true,
            'item' => $this->festivalApplication->getItem($uuid)->toArray(),
            'message' => 'Фестиваль отредактирован',
        ]);
    }

    /**
     * Удалить фестиваль (admin) — soft delete (запись помечается deleted_at).
     *
     * @throws Throwable
     */
    public function delete(string $id): JsonResponse
    {
        $uuid = new Uuid($id);

        if (null === $this->festivalApplication->getItem($uuid)) {
            return response()->json([
                'success' => false,
                'message' => 'Фестиваль не найден',
            ]);
        }

        return response()->json([
            'success' => $this->festivalApplication->delete($uuid, $this->authorId()),
        ]);
    }

    /**
     * Журнал изменений фестиваля (domain_history, aggregate_type=festival): кто/что/когда.
     */
    public function getHistory(string $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'history' => array_map(static fn (DomainHistoryDto $item): array => [
                'event_name' => $item->eventName,
                'aggregate_type' => $item->aggregateType,
                'payload' => $item->payload,
                'actor_id' => $item->actorRealId ?? $item->actorId,
                'actor_type' => $item->actorType,
                'actor_name' => $item->actorName,
                'actor_email' => $item->actorEmail,
                'occurred_at' => $item->occurredAt->toIso8601String(),
            ], $this->festivalApplication->getHistory(new Uuid($id))),
        ]);
    }

    /** Id текущего админа строкой (Auth::id() в проекте может вернуть Uuid VO). */
    private function authorId(): ?string
    {
        $id = Auth::id();

        if ($id === null) {
            return null;
        }

        return $id instanceof Uuid ? $id->value() : (string) $id;
    }

}
