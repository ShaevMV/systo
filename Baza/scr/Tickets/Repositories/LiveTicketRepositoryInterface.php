<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use Baza\Tickets\Applications\Search\LiveTicket\LiveTicketResponse;

interface LiveTicketRepositoryInterface
{
    public function search(int $kilter): ?LiveTicketResponse;

    public function skip(int $id, int $userId): bool;

    public function create(int $start, int $end): bool;
}
