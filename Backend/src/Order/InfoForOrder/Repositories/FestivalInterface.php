<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Repositories;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\InfoForOrder\DTO\FestivalDto;

interface FestivalInterface
{
    public function getFestivalByTicketTypeId(Uuid $ticketTypeId): FestivalDto;
}
