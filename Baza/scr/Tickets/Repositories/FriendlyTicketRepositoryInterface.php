<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use Baza\Tickets\Applications\Search\FriendlyTicket\FriendlyTicketResponse;

interface FriendlyTicketRepositoryInterface
{
    public function search(int $kilter): ?FriendlyTicketResponse;

    public function skip(int $id, int $userId): bool;
}
