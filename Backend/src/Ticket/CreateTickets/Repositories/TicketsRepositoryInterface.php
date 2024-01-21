<?php

namespace Tickets\Ticket\CreateTickets\Repositories;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Dto\PushTicketsDto;
use Tickets\Ticket\CreateTickets\Dto\TicketDto;

interface TicketsRepositoryInterface
{
    public function createTickets(TicketDto $ticketDto): bool;

    public function deleteTicketsByOrderId(Uuid $orderId): bool;

    /**
     * @param Uuid $orderId
     * @return Uuid[]
     */
    public function getListIdByOrderId(Uuid $orderId, bool $isShowDelete = false): array;

    public function getTicket(Uuid $ticketId, bool $isShowDelete = false): TicketResponse;

    public function setInBaza(TicketResponse $ticketsDto): bool;

    /**
     * @return Uuid[]
     */
    public function getAllTicketsId(Uuid $festivalId): array;
}
