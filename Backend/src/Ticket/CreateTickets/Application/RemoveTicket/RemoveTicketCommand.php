<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\RemoveTicket;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;

class RemoveTicketCommand implements Command
{
    public function __construct(
        private Uuid $orderId,
        private Uuid $orderTicketId,
        private ?string $actorId = null,
    )
    {
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }

    /**
     * @return Uuid
     */
    public function getOrderTicketId(): Uuid
    {
        return $this->orderTicketId;
    }

    public function getActorId(): ?string
    {
        return $this->actorId;
    }

}
