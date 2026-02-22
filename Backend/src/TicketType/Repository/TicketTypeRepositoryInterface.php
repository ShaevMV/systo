<?php

declare(strict_types=1);

namespace Tickets\TicketType\Repository;

use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Tickets\TicketType\Application\GetList\TicketTypeGetListFilter;
use Tickets\TicketType\Dto\TicketTypeDto;

interface TicketTypeRepositoryInterface
{
    public function getList(TicketTypeGetListFilter $filters, Order $orderBy): Collection;
    public function getItem(Uuid $id): TicketTypeDto;
    public function editItem(Uuid $id, TicketTypeDto $data): bool;
    public function create(TicketTypeDto $data): bool;
    public function remove(Uuid $id): bool;
}
