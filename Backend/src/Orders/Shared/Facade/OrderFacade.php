<?php

declare(strict_types=1);

namespace Tickets\Orders\Shared\Facade;

use Bus;
use DomainException;
use Illuminate\Support\Facades\DB;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\History\Domain\ActorType;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\Orders\Friendly\Domain\FriendlyOrder;
use Tickets\Orders\Friendly\Dto\FriendlyOrderDto;
use Tickets\Orders\Friendly\Repository\FriendlyOrderRepositoryInterface;
use Tickets\Orders\Guest\Domain\GuestOrder;
use Tickets\Orders\Guest\Dto\GuestOrderDto;
use Tickets\Orders\Guest\Repository\GuestOrderRepositoryInterface;
use Tickets\Orders\Live\Domain\LiveOrder;
use Tickets\Orders\Live\Dto\LiveOrderDto;
use Tickets\Orders\Live\Repository\LiveOrderRepositoryInterface;
use Tickets\Orders\Shared\Domain\BaseOrder;
use Tickets\Orders\Shared\Response\OrderItemResponse;
use Tickets\Orders\Shared\Response\OrderListItemResponse;

/**
 * Единая точка входа для работы со всеми типами заказов.
 *
 * Facade оркестрирует:
 * - Сохранение нового заказа в репозиторий
 * - Создание доменного агрегата с корректным kilter
 * - Диспатч Domain Events через Bus::chain()
 * - Запись истории через HistoryRepository
 * - Транзакционность (DB::transaction)
 *
 * Каждый тип заказа имеет отдельный репозиторий и методы в Facade.
 * Это обеспечивает явность зависимостей и упрощает тестирование.
 *
 * Источник: Роберт Мартин — «Чистая архитектура», Pattern Facade.
 */
final class OrderFacade
{
    public function __construct(
        private readonly GuestOrderRepositoryInterface    $guestRepository,
        private readonly FriendlyOrderRepositoryInterface $friendlyRepository,
        private readonly LiveOrderRepositoryInterface     $liveRepository,
        private readonly HistoryRepositoryInterface       $historyRepository,
    ) {}

    // ----------------------------------------------------------------
    // Создание заказов
    // ----------------------------------------------------------------

    /**
     * Создаёт гостевой заказ: сохраняет в БД, диспатчит события.
     *
     * @throws Throwable
     */
    public function createGuest(
        GuestOrderDto $dto,
        ?Uuid         $actorId   = null,
        string        $actorType = ActorType::SYSTEM,
    ): GuestOrder {
        return DB::transaction(function () use ($dto, $actorId, $actorType): GuestOrder {
            $kilter = $this->guestRepository->create($dto);
            $order  = GuestOrder::create($dto, $kilter);

            $this->publishEvents($order, $dto->getId()->value(), $actorId, $actorType);

            return $order;
        });
    }

    /**
     * Создаёт дружеский заказ (сразу в PAID): сохраняет в БД, диспатчит события.
     *
     * @throws Throwable
     */
    public function createFriendly(
        FriendlyOrderDto $dto,
        ?Uuid            $actorId   = null,
        string           $actorType = ActorType::USER,
    ): FriendlyOrder {
        return DB::transaction(function () use ($dto, $actorId, $actorType): FriendlyOrder {
            $kilter = $this->friendlyRepository->create($dto);
            $order  = FriendlyOrder::create($dto, $kilter);

            $this->publishEvents($order, $dto->getId()->value(), $actorId, $actorType);

            return $order;
        });
    }

    /**
     * Создаёт живой заказ (NEW_FOR_LIVE): сохраняет в БД, диспатчит события.
     *
     * @throws Throwable
     */
    public function createLive(
        LiveOrderDto $dto,
        ?Uuid        $actorId   = null,
        string       $actorType = ActorType::SYSTEM,
    ): LiveOrder {
        return DB::transaction(function () use ($dto, $actorId, $actorType): LiveOrder {
            $kilter = $this->liveRepository->create($dto);
            $order  = LiveOrder::create($dto, $kilter);

            $this->publishEvents($order, $dto->getId()->value(), $actorId, $actorType);

            return $order;
        });
    }

    // ----------------------------------------------------------------
    // Смена статусов
    // ----------------------------------------------------------------

    /**
     * Меняет статус гостевого заказа.
     *
     * Params для конкретных переходов:
     * - PAID:               ['email' => string, 'comment' => ?string, 'externalPromoCode' => ?ExternalPromoCodeDto]
     * - CANCEL:             ['email' => string]
     * - DIFFICULTIES_AROSE: ['email' => string, 'comment' => string]
     *
     * @throws Throwable
     */
    public function changeGuestStatus(
        Uuid   $orderId,
        Status $newStatus,
        array  $params    = [],
        ?Uuid  $actorId   = null,
        string $actorType = ActorType::USER,
    ): GuestOrder {
        return DB::transaction(function () use ($orderId, $newStatus, $params, $actorId, $actorType): GuestOrder {
            $order = $this->guestRepository->findById($orderId)
                ?? throw new DomainException("Гостевой заказ {$orderId->value()} не найден");

            $order->processStatusChange($newStatus, $params);
            $this->guestRepository->save($order);
            $this->publishEvents($order, $orderId->value(), $actorId, $actorType);

            return $order;
        });
    }

    /**
     * Меняет статус дружеского заказа.
     *
     * Params для конкретных переходов:
     * - CANCEL: ['email' => string]
     *
     * @throws Throwable
     */
    public function changeFriendlyStatus(
        Uuid   $orderId,
        Status $newStatus,
        array  $params    = [],
        ?Uuid  $actorId   = null,
        string $actorType = ActorType::USER,
    ): FriendlyOrder {
        return DB::transaction(function () use ($orderId, $newStatus, $params, $actorId, $actorType): FriendlyOrder {
            $order = $this->friendlyRepository->findById($orderId)
                ?? throw new DomainException("Дружеский заказ {$orderId->value()} не найден");

            $order->processStatusChange($newStatus, $params);
            $this->friendlyRepository->save($order);
            $this->publishEvents($order, $orderId->value(), $actorId, $actorType);

            return $order;
        });
    }

    /**
     * Меняет статус живого заказа.
     *
     * Params для конкретных переходов:
     * - PAID_FOR_LIVE:      ['email' => string, 'kilter' => int]
     * - LIVE_TICKET_ISSUED: ['liveNumbers' => [ticketId => liveNumber]]
     * - CANCEL:             ['email' => string]
     * - CANCEL_FOR_LIVE:    []
     * - DIFFICULTIES_AROSE: ['email' => string, 'comment' => string]
     *
     * @throws Throwable
     */
    public function changeLiveStatus(
        Uuid   $orderId,
        Status $newStatus,
        array  $params    = [],
        ?Uuid  $actorId   = null,
        string $actorType = ActorType::USER,
    ): LiveOrder {
        return DB::transaction(function () use ($orderId, $newStatus, $params, $actorId, $actorType): LiveOrder {
            $order = $this->liveRepository->findById($orderId)
                ?? throw new DomainException("Живой заказ {$orderId->value()} не найден");

            $order->processStatusChange($newStatus, $params);
            $this->liveRepository->save($order);
            $this->publishEvents($order, $orderId->value(), $actorId, $actorType);

            return $order;
        });
    }

    // ----------------------------------------------------------------
    // Поиск
    // ----------------------------------------------------------------

    public function findGuest(Uuid $id): ?GuestOrder
    {
        return $this->guestRepository->findById($id);
    }

    public function findFriendly(Uuid $id): ?FriendlyOrder
    {
        return $this->friendlyRepository->findById($id);
    }

    public function findLive(Uuid $id): ?LiveOrder
    {
        return $this->liveRepository->findById($id);
    }

    // ----------------------------------------------------------------
    // Чтение — guest
    // ----------------------------------------------------------------

    public function getGuestItem(Uuid $id): ?OrderItemResponse
    {
        return $this->guestRepository->getItem($id);
    }

    /** @return OrderListItemResponse[] */
    public function getGuestUserList(Uuid $userId): array
    {
        return $this->guestRepository->getUserList($userId);
    }

    /** @return OrderListItemResponse[] */
    public function getGuestList(?string $status = null, ?Uuid $festivalId = null): array
    {
        return $this->guestRepository->getList($status, $festivalId);
    }

    // ----------------------------------------------------------------
    // Чтение — friendly
    // ----------------------------------------------------------------

    public function getFriendlyItem(Uuid $id): ?OrderItemResponse
    {
        return $this->friendlyRepository->getItem($id);
    }

    /** @return OrderListItemResponse[] */
    public function getFriendlyUserList(Uuid $userId): array
    {
        return $this->friendlyRepository->getUserList($userId);
    }

    /** @return OrderListItemResponse[] */
    public function getFriendlyList(?string $status = null, ?Uuid $festivalId = null): array
    {
        return $this->friendlyRepository->getList($status, $festivalId);
    }

    // ----------------------------------------------------------------
    // Чтение — live
    // ----------------------------------------------------------------

    public function getLiveItem(Uuid $id): ?OrderItemResponse
    {
        return $this->liveRepository->getItem($id);
    }

    /** @return OrderListItemResponse[] */
    public function getLiveUserList(Uuid $userId): array
    {
        return $this->liveRepository->getUserList($userId);
    }

    /** @return OrderListItemResponse[] */
    public function getLiveList(?string $status = null, ?Uuid $festivalId = null): array
    {
        return $this->liveRepository->getList($status, $festivalId);
    }

    // ----------------------------------------------------------------
    // Приватная оркестрация
    // ----------------------------------------------------------------

    /**
     * Сохраняет историю и диспатчит Domain Events агрегата.
     *
     * Порядок важен: история сохраняется до диспатча событий,
     * чтобы при сбое очереди история не пропала.
     */
    private function publishEvents(
        BaseOrder $order,
        string    $aggregateId,
        ?Uuid     $actorId,
        string    $actorType,
    ): void {
        $domainEvents = $order->pullDomainEvents();

        foreach ($order->pullHistoryEvents() as $historyEvent) {
            $this->historyRepository->save(new SaveHistoryDto(
                aggregateId: $aggregateId,
                event:       $historyEvent,
                actorId:     $actorId?->value(),
                actorType:   $actorType,
            ));
        }

        if (!empty($domainEvents)) {
            Bus::chain($domainEvents)->dispatch();
        }
    }
}
