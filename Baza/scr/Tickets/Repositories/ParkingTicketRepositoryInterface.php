<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use Baza\Tickets\Responses\LiveTicketResponse;
use Baza\Tickets\Responses\ParkingTicketResponse;

interface ParkingTicketRepositoryInterface
{
    public function search(int $kilter, string $type): ?ParkingTicketResponse;

    public function skip(int $id, int $userId): bool;

    public function create(int $start, int $end, string $type): bool;

    /**
     * @param string $q
     * @return LiveTicketResponse[]
     */
    public function find(string $q, string $type): array;
}
