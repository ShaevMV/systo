<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use Baza\Shared\Domain\ValueObject\Uuid;
use Baza\Tickets\Responses\ElTicketResponse;

interface ElTicketsRepositoryInterface
{
    public function search(Uuid $id): ?ElTicketResponse;

    public function skip(int $id, int $userId): bool;

    /**
     * @param string $q
     * @return ElTicketResponse[]
     */
    public function find(string $q): array;
}
