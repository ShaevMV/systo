<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use Baza\Tickets\Applications\Search\ElTicket\ElTicketResponse;
use Tickets\Shared\Domain\ValueObject\Uuid;

interface ElTicketsRepositoryInterface
{
    public function search(Uuid $id): ?ElTicketResponse;
}
