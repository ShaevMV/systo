<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use Baza\Tickets\Applications\Search\ElTicket\ElTicketResponse;
use Baza\Shared\Domain\ValueObject\Uuid;

interface ElTicketsRepositoryInterface
{
    public function search(Uuid $id): ?ElTicketResponse;

    public function skip(int $id, int $userId): bool;
}
