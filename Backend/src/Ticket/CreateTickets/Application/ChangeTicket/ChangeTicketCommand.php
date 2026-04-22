<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\ChangeTicket;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;

class ChangeTicketCommand implements Command
{
    public function __construct(
        private Uuid  $orderId,
        private array $valueMap,
        private array $emailMap,
    ) {
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }

    /** @return array [ticketId => newValue] */
    public function getValueMap(): array
    {
        return $this->valueMap;
    }

    /** @return array [ticketId => newEmail] */
    public function getEmailMap(): array
    {
        return $this->emailMap;
    }
}
