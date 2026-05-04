<?php

declare(strict_types=1);

namespace Tickets\Location\Repositories;

use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Location\Dto\LocationDto;

interface LocationRepositoryInterface
{
    public function getList(Filters $filters, Order $orderBy): Collection;

    public function getItem(Uuid $id): LocationDto;

    public function create(LocationDto $data): bool;

    public function editItem(Uuid $id, LocationDto $data): bool;

    public function remove(Uuid $id): bool;
}
