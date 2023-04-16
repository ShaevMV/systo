<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Repositories;

use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Application\PushTicket\Get\PushTicketsResponse;
use Tickets\Ticket\CreateTickets\Dto\PushTicketsDto;

interface PushTicketsRepositoryInterface
{
    public function getTicket(Uuid $ticketId): PushTicketsDto;

    /**
     * @param Uuid|null $uuid
     * @return PushTicketsDto[];
     */
    public function getTicketsAllOrFirst(?Uuid $uuid): array;

    public function setInBaza(PushTicketsDto $ticketsDto): bool;
}
