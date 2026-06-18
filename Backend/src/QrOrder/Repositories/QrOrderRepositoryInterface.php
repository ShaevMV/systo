<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Tickets\QrOrder\Dto\QrOrderDto;
use Tickets\QrOrder\Responses\QrOrderItemForListResponse;

interface QrOrderRepositoryInterface
{
    public function create(QrOrderDto $dto): bool;

    /**
     * Страница списка для админки (read-only). Возвращает облегчённые проекции без payload.
     *
     * @return Collection<int, QrOrderItemForListResponse>
     */
    public function getList(Filters $filters, Order $orderBy, int $page, int $perPage): Collection;

    /** Общее число заказов под теми же фильтрами (для пагинации totalNumber). */
    public function countList(Filters $filters): int;

    /**
     * Сводные агрегаты для дашборда (read-only): заказы + выручка, всего и в разрезах
     * (по статусу, по типу заказа, по дням). Деньги — целые рубли (как total_price).
     *
     * @param  array{festival_id?: ?string, date_from?: ?string, date_to?: ?string}  $filter
     * @return array{
     *     totals: array{orders: int, revenue: int},
     *     byStatus: array<int, array{status: string, orders: int, revenue: int}>,
     *     byType: array<int, array{type_order: ?string, orders: int, revenue: int}>,
     *     timeseries: array<int, array{date: string, orders: int, revenue: int}>
     * }
     */
    public function aggregateStats(array $filter): array;

    /** Заказ qr с таким id уже принят (id == id заказа org → идемпотентность приёма). */
    public function existsById(Uuid $id): bool;

    public function findById(Uuid $id): ?QrOrderDto;

    /** Сменить статус принятого заказа (API №2). */
    public function changeStatus(Uuid $id, string $status): bool;

    /** Отметить заказ как выданный (билеты созданы) — защита от повторной выдачи. */
    public function markIssued(Uuid $id, Carbon $issuedAt): bool;

    /** Снять отметку выдачи (issued_at = null) — при сбое выдачи, чтобы заказ можно было выдать повторно. */
    public function clearIssued(Uuid $id): bool;
}
