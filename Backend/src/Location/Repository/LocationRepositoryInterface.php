<?php

declare(strict_types=1);

namespace Tickets\Location\Repository;

use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Location\Application\GetList\LocationGetListFilter;
use Tickets\Location\Dto\LocationDto;

interface LocationRepositoryInterface
{
    public function getList(LocationGetListFilter $filters, Order $orderBy): Collection;

    public function getItem(Uuid $id): LocationDto;

    public function create(LocationDto $data): bool;

    public function editItem(Uuid $id, LocationDto $data): bool;

    public function remove(Uuid $id): bool;
}
