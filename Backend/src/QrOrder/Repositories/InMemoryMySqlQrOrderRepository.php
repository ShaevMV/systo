<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Repositories;

use App\Models\QrOrder\QrOrderModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\Filter\FilterBuilder;
use Shared\Domain\ValueObject\Uuid;
use Tickets\QrOrder\Dto\QrOrderDto;
use Tickets\QrOrder\Responses\QrOrderItemForListResponse;

final class InMemoryMySqlQrOrderRepository implements QrOrderRepositoryInterface
{
    public function __construct(
        private QrOrderModel $model,
    ) {}

    public function create(QrOrderDto $dto): bool
    {
        // create() (а не insert) — чтобы сработал каст 'payload' => 'array' (JSON кодируется один раз).
        return (bool) $this->model::create([
            'id' => $dto->getId()->value(),
            'email' => $dto->getEmail(),
            'status' => $dto->getStatus(),
            'festival_id' => $dto->getFestivalId()?->value(),
            'type_order' => $dto->getTypeOrder(),
            'city' => $dto->getCity(),
            'phone' => $dto->getPhone(),
            'total_price' => $dto->getTotalPrice(),
            'payload' => $dto->getPayload(),
            'external_order_no' => $dto->getExternalOrderNo(),
            'payment_method' => $dto->getPaymentMethod(),
            'promo_code' => $dto->getPromoCode(),
            'paid_at' => $dto->getPaidAt(),
            'buyer_fio' => $dto->getBuyerFio(),
            'festival_title' => $dto->getFestivalTitle(),
        ]);
    }

    public function getList(Filters $filters, Order $orderBy, int $page, int $perPage): Collection
    {
        $build = FilterBuilder::build($this->model::query(), $filters);

        if ($orderBy->orderBy()->value()) {
            $build = $build->orderBy(
                $orderBy->orderBy()->value(),
                $orderBy->orderType()->value(),
            );
        }

        return $build->forPage($page, $perPage)
            ->get()
            ->map(fn (QrOrderModel $model) => QrOrderItemForListResponse::fromState($model->toArray()));
    }

    public function countList(Filters $filters): int
    {
        return FilterBuilder::build($this->model::query(), $filters)->count();
    }

    /**
     * Сводные агрегаты для дашборда. Считаем на стороне БД (COUNT/SUM/GROUP BY) —
     * без загрузки строк в PHP. Фильтр — festival_id + диапазон дат created_at.
     *
     * @param  array{festival_id?: ?string, date_from?: ?string, date_to?: ?string}  $filter
     * @return array{
     *     totals: array{orders: int, revenue: int},
     *     byStatus: array<int, array{status: string, orders: int, revenue: int}>,
     *     byType: array<int, array{type_order: ?string, orders: int, revenue: int}>,
     *     timeseries: array<int, array{date: string, orders: int, revenue: int}>,
     *     byPaymentMethod: array<int, array{payment_method: ?string, orders: int, revenue: int}>
     * }
     */
    public function aggregateStats(array $filter): array
    {
        // Замыкание навешивает одни и те же условия на каждый из 4 агрегирующих запросов.
        $scoped = function () use ($filter) {
            $query = $this->model::query();

            if (! empty($filter['festival_id'])) {
                $query->where(QrOrderModel::TABLE.'.festival_id', $filter['festival_id']);
            }
            if (! empty($filter['date_from'])) {
                $query->whereDate(QrOrderModel::TABLE.'.created_at', '>=', $filter['date_from']);
            }
            if (! empty($filter['date_to'])) {
                $query->whereDate(QrOrderModel::TABLE.'.created_at', '<=', $filter['date_to']);
            }

            return $query;
        };

        $totals = $scoped()
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total_price), 0) as revenue')
            ->first();

        $byStatus = $scoped()
            ->selectRaw('status, COUNT(*) as orders, COALESCE(SUM(total_price), 0) as revenue')
            ->groupBy('status')
            ->orderByDesc('orders')
            ->get()
            ->map(static fn ($row) => [
                'status' => (string) $row->status,
                'orders' => (int) $row->orders,
                'revenue' => (int) $row->revenue,
            ])->all();

        $byType = $scoped()
            ->selectRaw('type_order, COUNT(*) as orders, COALESCE(SUM(total_price), 0) as revenue')
            ->groupBy('type_order')
            ->orderByDesc('orders')
            ->get()
            ->map(static fn ($row) => [
                'type_order' => $row->type_order,
                'orders' => (int) $row->orders,
                'revenue' => (int) $row->revenue,
            ])->all();

        $timeseries = $scoped()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, COALESCE(SUM(total_price), 0) as revenue')
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at)')
            ->get()
            ->map(static fn ($row) => [
                'date' => (string) $row->date,
                'orders' => (int) $row->orders,
                'revenue' => (int) $row->revenue,
            ])->all();

        // Отчётность по способу оплаты (проекция payment_method из расширенного контракта qr).
        $byPaymentMethod = $scoped()
            ->selectRaw('payment_method, COUNT(*) as orders, COALESCE(SUM(total_price), 0) as revenue')
            ->groupBy('payment_method')
            ->orderByDesc('orders')
            ->get()
            ->map(static fn ($row) => [
                'payment_method' => $row->payment_method,
                'orders' => (int) $row->orders,
                'revenue' => (int) $row->revenue,
            ])->all();

        return [
            'totals' => [
                'orders' => (int) ($totals->orders ?? 0),
                'revenue' => (int) ($totals->revenue ?? 0),
            ],
            'byStatus' => $byStatus,
            'byType' => $byType,
            'timeseries' => $timeseries,
            'byPaymentMethod' => $byPaymentMethod,
        ];
    }

    public function existsById(Uuid $id): bool
    {
        return $this->model::whereId($id->value())->exists();
    }

    public function findById(Uuid $id): ?QrOrderDto
    {
        $row = $this->model::whereId($id->value())->first();

        return $row === null ? null : QrOrderDto::fromState($row->toArray());
    }

    public function changeStatus(Uuid $id, string $status): bool
    {
        return (bool) $this->model::whereId($id->value())->update(['status' => $status]);
    }

    public function markIssued(Uuid $id, Carbon $issuedAt): bool
    {
        return (bool) $this->model::whereId($id->value())->update(['issued_at' => $issuedAt]);
    }

    public function clearIssued(Uuid $id): bool
    {
        return (bool) $this->model::whereId($id->value())->update(['issued_at' => null]);
    }
}
