<?php

declare(strict_types=1);

namespace Tickets\Festival\Repositories;

use Carbon\Carbon;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Festival\Response\TicketTypeDto;

interface TicketTypeInterfaceRepository
{
    /**
     * @return TicketTypeDto[]
     */
    public function getList(Uuid $festivalId, bool $isAllPrice = false, ?Carbon $afterDate = null): array;

    public function getNameById(): array;

    public function getById(
        Uuid    $uuid,
        ?Carbon $afterDate = null,
    ): TicketTypeDto;

    public function getListPrice(Uuid $festivalId): array;

    public function create(TicketTypeDto $typeDto): bool;
}
