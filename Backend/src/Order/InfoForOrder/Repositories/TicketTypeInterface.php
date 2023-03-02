<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Repositories;

use Carbon\Carbon;
use Tickets\Order\InfoForOrder\Response\TicketTypeDto;
use Tickets\Shared\Domain\ValueObject\Uuid;

interface TicketTypeInterface
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
}
