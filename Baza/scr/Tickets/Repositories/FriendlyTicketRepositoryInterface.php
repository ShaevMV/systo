<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use Baza\Tickets\Responses\FriendlyTicketResponse;

interface FriendlyTicketRepositoryInterface
{
    public function search(int $kilter): ?FriendlyTicketResponse;

    public function skip(int $id, int $userId): bool;

    /**
     * @param string $q
     * @return FriendlyTicketResponse[]
     */
    public function find(string $q): array;
}
