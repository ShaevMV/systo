<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use Baza\Tickets\Responses\SpisokTicketResponse;
use Shared\Domain\ValueObject\Uuid;

interface SpisokTicketsRepositoryInterface
{
    public function search(Uuid $kilter): ?SpisokTicketResponse;

    public function skip(Uuid $id, int $userId): bool;

    /**
     * @param string $q
     * @return SpisokTicketResponse[]
     */
    public function find(string $q): array;
}
