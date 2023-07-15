<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Repositories;

use Tickets\Order\OrderTicket\Dto\Festival\FestivalDto;
use Shared\Domain\ValueObject\Uuid;

interface FestivalRepositoryInterface
{
    public function get(Uuid $id): FestivalDto;
}
