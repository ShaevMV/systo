<?php

namespace Baza\Tickets\Repositories;

use Baza\Tickets\Responses\AutoTicketResponse;

interface AutoTicketRepositoryInterface
{
    public function skip(int $id, int $userId): bool;

    /**
     * @param string $q
     * @return AutoTicketResponse[]
     */
    public function find(string $q): array;
}
