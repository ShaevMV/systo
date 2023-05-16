<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Repositories;

use Carbon\Carbon;
use Tickets\Order\InfoForOrder\Response\TicketTypeDto;
use Tickets\Shared\Domain\ValueObject\Uuid;

interface TicketTypeInterfaceRepository
{
    /**
     * @return TicketTypeDto[]
     */
    public function getList(Carbon $afterDate): array;

    public function getById(
        Uuid $uuid,
        ?Carbon $afterDate = null,
    ): TicketTypeDto;

    public function getListPrice(): array;

    public function create(TicketTypeDto $typeDto): bool;
}
