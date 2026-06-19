<?php

declare(strict_types=1);

namespace Tickets\BazaDelivery\Repositories;

use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Tickets\BazaDelivery\Dto\BazaDeliveryDto;

/**
 * Хранилище трекинга доставки билетов в Baza. БД только здесь (правило №1). Текущий статус
 * доставки; таймлайн всех попыток — в domain_history через HistoryRepositoryInterface.
 * Зеркало EmailMessageRepositoryInterface.
 */
interface BazaDeliveryRepositoryInterface
{
    /** Создать запись доставки (status=queued). Одна строка на (ticket_id, target) — UNIQUE. */
    public function create(BazaDeliveryDto $dto): bool;

    public function findById(Uuid $id): ?BazaDeliveryDto;

    /** Текущая доставка по (билет, цель) — для идемпотентного диспатча (создать/повторить). */
    public function findByTicketTarget(Uuid $ticketId, string $target): ?BazaDeliveryDto;

    /** status → sending, attempts++ (новая попытка записи в Baza). */
    public function markSending(Uuid $id): bool;

    /** status → delivered, delivered_at = now (билет записан в Baza). */
    public function markDelivered(Uuid $id): bool;

    /** status → failed, error = причина = «где застряло». */
    public function markFailed(Uuid $id, string $error): bool;

    /** status → queued (авто-ретрай / ручной повтор из админки). */
    public function requeue(Uuid $id): bool;

    /** Страница списка для админки (проекции BazaDeliveryItemForListResponse). */
    public function getList(Filters $filters, Order $orderBy, int $page, int $perPage): Collection;

    public function countList(Filters $filters): int;

    /** Доставки билетов заказа (для экрана qr — «весь путь» заказа). */
    public function getByOrderId(Uuid $orderId): Collection;

    /** Число застрявших доставок (status=failed) — для дашборд-виджета. */
    public function countStuck(?Uuid $festivalId): int;

    /**
     * Счётчики доставок по статусам (+ alias stuck=failed) для дашборда/статистики.
     *
     * @return array<string, int>
     */
    public function statusCounts(?Uuid $festivalId): array;
}
