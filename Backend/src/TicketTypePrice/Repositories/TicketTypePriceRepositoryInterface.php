<?php

declare(strict_types=1);

namespace Tickets\TicketTypePrice\Repositories;

use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Tickets\TicketTypePrice\Dto\TicketTypePriceDto;

interface TicketTypePriceRepositoryInterface
{
    public function getList(Filters $filters, Order $orderBy): Collection;

    public function getItem(Uuid $id): TicketTypePriceDto;

    public function create(TicketTypePriceDto $data): bool;

    public function editItem(Uuid $id, TicketTypePriceDto $data): bool;

    public function remove(Uuid $id): bool;
}
