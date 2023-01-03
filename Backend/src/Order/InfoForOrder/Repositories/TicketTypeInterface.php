<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Repositories;

use Tickets\Order\InfoForOrder\Response\TicketTypeDto;
use Tickets\Shared\Domain\ValueObject\Uuid;

interface TicketTypeInterface
{
    /**
     * @return TicketTypeDto[]
     */
    public function getList(): array;

    public function getById(Uuid $uuid): TicketTypeDto;
}
