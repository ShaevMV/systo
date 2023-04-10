<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use Baza\Tickets\Applications\Search\SpisokTicket\SpisokTicketResponse;

interface SpisokTicketsRepositoryInterface
{
    public function search(int $kilter): ?SpisokTicketResponse;
}
