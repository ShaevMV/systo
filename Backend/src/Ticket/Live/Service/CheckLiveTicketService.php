<?php

declare(strict_types=1);

namespace Tickets\Ticket\Live\Service;

use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

class CheckLiveTicketService
{
    public function __construct(
        private TicketsRepositoryInterface $repository
    )
    {
    }

    public function checkLiveNumber(int $number): bool
    {
        return $this->repository->checkLiveNumber($number);
    }
}
