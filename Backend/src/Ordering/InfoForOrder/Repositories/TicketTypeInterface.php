<?php

declare(strict_types=1);

namespace Tickets\Ordering\InfoForOrder\Repositories;

use Tickets\Ordering\InfoForOrder\Response\TicketTypeDto;
use Tickets\Shared\Domain\ValueObject\Uuid;

interface TicketTypeInterface
{
    /**
     * @return TicketTypeDto[]
     */
    public function getList(): array;

    public function getById(Uuid $uuid): TicketTypeDto;
}
