<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use Baza\Tickets\Responses\SpisokTicketResponse;

interface SpisokTicketsRepositoryInterface
{
    public function search(int $kilter): ?SpisokTicketResponse;

    public function skip(int $id, int $userId): bool;

    /**
     * @param string $q
     * @return SpisokTicketResponse[]
     */
    public function find(string $q): array;
}
