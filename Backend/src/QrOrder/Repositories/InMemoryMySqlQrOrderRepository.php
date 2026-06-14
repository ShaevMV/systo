<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Repositories;

use App\Models\QrOrder\QrOrderModel;
use Shared\Domain\ValueObject\Uuid;
use Tickets\QrOrder\Dto\QrOrderDto;

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
}
