<?php

declare(strict_types = 1);

namespace Tickets\Ordering\OrderTicket\Repositories;

use Tickets\Ordering\OrderTicket\Domain\OrderTicketItem;
use Tickets\Ordering\OrderTicket\Dto\OrderTicketDto;
use Tickets\Shared\Domain\Criteria\Filter;
use Tickets\Shared\Domain\Criteria\Filters;
use Tickets\Shared\Domain\ValueObject\Uuid;

interface OrderTicketRepositoryInterface
{
    public function create(OrderTicketDto $orderTicketDto): bool;

    public function getUserList(Uuid $userId): array;

    public function findOrder(Uuid $uuid): ?OrderTicketItem;

    public function getList(Filters $filters): array;
}
