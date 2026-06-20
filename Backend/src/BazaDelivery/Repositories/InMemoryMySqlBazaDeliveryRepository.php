<?php

declare(strict_types=1);

namespace Tickets\BazaDelivery\Repositories;

use App\Models\BazaDelivery\BazaDeliveryModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\Filter\FilterBuilder;
use Shared\Domain\ValueObject\Uuid;
use Tickets\BazaDelivery\Domain\ValueObject\BazaDeliveryStatus;
use Tickets\BazaDelivery\Dto\BazaDeliveryDto;
use Tickets\BazaDelivery\Responses\BazaDeliveryItemForListResponse;

final class InMemoryMySqlBazaDeliveryRepository implements BazaDeliveryRepositoryInterface
{
    public function __construct(
        private BazaDeliveryModel $model,
    ) {}

    public function create(BazaDeliveryDto $dto, ?string $subjectBlob = null, ?string $searchBlob = null): bool
    {
        // create() (а не insert) — единая точка форматирования через Eloquent-касты.
        return (bool) $this->model::create(array_merge(
            $dto->toArrayForCreate(),
            ['subject_blob' => $subjectBlob, 'search_blob' => $searchBlob],
        ));
    }

    public function findById(Uuid $id): ?BazaDeliveryDto
    {
        $row = $this->model::whereId($id->value())->first();

        return $row === null ? null : BazaDeliveryDto::fromState($row->toArray());
    }

    public function getSubjectBlob(Uuid $id): ?string
    {
        return $this->model::query()->select('subject_blob')->whereId($id->value())->first()?->subject_blob;
    }

    public function getSearchBlob(Uuid $id): ?string
    {
        return $this->model::query()->select('search_blob')->whereId($id->value())->first()?->search_blob;
    }

    public function findByTicketTarget(Uuid $ticketId, string $target): ?BazaDeliveryDto
    {
        $row = $this->model::query()
            ->where('ticket_id', $ticketId->value())
            ->where('target', $target)
            ->first();

        return $row === null ? null : BazaDeliveryDto::fromState($row->toArray());
    }

    public function markSending(Uuid $id): bool
    {
        $row = $this->model::whereId($id->value())->first();
        if ($row === null) {
            return false;
        }
        $row->status = BazaDeliveryStatus::SENDING;
        $row->attempts = (int) $row->attempts + 1;

        return $row->save();
    }

    public function markDelivered(Uuid $id): bool
    {
        return (bool) $this->model::whereId($id->value())->update([
            'status' => BazaDeliveryStatus::DELIVERED,
            'delivered_at' => Carbon::now(),
            'error' => null,
        ]);
    }

    public function markFailed(Uuid $id, string $error): bool
    {
        return (bool) $this->model::whereId($id->value())->update([
            'status' => BazaDeliveryStatus::FAILED,
            'error' => mb_substr($error, 0, 2000),
        ]);
    }

    public function requeue(Uuid $id): bool
    {
        return (bool) $this->model::whereId($id->value())->update([
            'status' => BazaDeliveryStatus::QUEUED,
            'error' => null,
        ]);
    }

    public function getList(Filters $filters, Order $orderBy, int $page, int $perPage): Collection
    {
        $build = FilterBuilder::build($this->model::query(), $filters);

        if ($orderBy->orderBy()->value()) {
            $build = $build->orderBy($orderBy->orderBy()->value(), $orderBy->orderType()->value());
        } else {
            $build = $build->orderByDesc('created_at');
        }

        return $build->forPage($page, $perPage)
            ->get()
            ->map(fn (BazaDeliveryModel $model) => BazaDeliveryItemForListResponse::fromState($model->toArray()));
    }

    public function countList(Filters $filters): int
    {
        return FilterBuilder::build($this->model::query(), $filters)->count();
    }

    public function getByOrderId(Uuid $orderId): Collection
    {
        return $this->model::query()
            ->where('order_id', $orderId->value())
            ->orderBy('created_at')
            ->get()
            ->map(fn (BazaDeliveryModel $model) => BazaDeliveryItemForListResponse::fromState($model->toArray()));
    }

    public function countStuck(?Uuid $festivalId): int
    {
        return $this->model::query()
            ->where('status', BazaDeliveryStatus::FAILED)
            ->when($festivalId !== null, fn ($q) => $q->where('festival_id', $festivalId->value()))
            ->count();
    }

    public function statusCounts(?Uuid $festivalId): array
    {
        $counts = $this->model::query()
            ->when($festivalId !== null, fn ($q) => $q->where('festival_id', $festivalId->value()))
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        return [
            BazaDeliveryStatus::QUEUED => (int) $counts->get(BazaDeliveryStatus::QUEUED, 0),
            BazaDeliveryStatus::SENDING => (int) $counts->get(BazaDeliveryStatus::SENDING, 0),
            BazaDeliveryStatus::DELIVERED => (int) $counts->get(BazaDeliveryStatus::DELIVERED, 0),
            BazaDeliveryStatus::FAILED => (int) $counts->get(BazaDeliveryStatus::FAILED, 0),
            'stuck' => (int) $counts->get(BazaDeliveryStatus::FAILED, 0),
        ];
    }
}
