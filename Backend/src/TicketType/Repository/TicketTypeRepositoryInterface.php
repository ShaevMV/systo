<?php

declare(strict_types=1);

namespace Tickets\TicketType\Repository;

use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\ValueObject\Uuid;
use Tickets\TicketType\Dto\TicketTypeDto;
use Tickets\TicketType\Response\TicketTypeGetListResponse;

interface TicketTypeRepositoryInterface
{
    public function getList(Filters $filters): Collection;
    public function getItem(Uuid $id): TicketTypeDto;
    public function editItem(Uuid $id, TicketTypeDto $data): bool;
    public function create(TicketTypeDto $data): bool;
    public function remove(Uuid $id): bool;
}
