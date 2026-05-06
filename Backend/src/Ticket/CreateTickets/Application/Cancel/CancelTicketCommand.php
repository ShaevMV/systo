<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\Cancel;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;

class CancelTicketCommand implements Command
{
    /**
     * @param Uuid $orderId
     * @param Uuid[] $ticketIds
     */
    public function __construct(
        private Uuid  $orderId,
        private array $ticketIds = [],
    )
    {
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }

    /**
     * @return Uuid[]
     */
    public function getTicketIds(): array
    {
        return $this->ticketIds;
    }
}
