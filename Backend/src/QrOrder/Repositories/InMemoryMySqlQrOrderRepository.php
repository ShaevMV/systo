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
    ) {
    }

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
