<?php

declare(strict_types=1);

namespace Tickets\Festival\Repositories;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Festival\DTO\FestivalDto;

interface FestivalInterface
{
    public function getFestivalByTicketTypeId(Uuid $ticketTypeId): FestivalDto;
}
