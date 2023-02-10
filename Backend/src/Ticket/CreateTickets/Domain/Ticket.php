<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Domain;

use Tickets\Shared\Domain\Aggregate\AggregateRoot;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

class Ticket extends AggregateRoot
{
    public function __construct(
        private Uuid $orderId,
        private string $name,
        private int $kilter,
        private Uuid $aggregateId,
    ) {
    }

    public static function newTicket(Uuid $orderId, string $quest, int $kilter, Uuid $id): self
    {
        $result = new self($orderId, $quest, $kilter, $id);

        $result->record(new ProcessCreatingQRCode(
            $result->aggregateId,
            $result->name,
            $result->kilter,
        ));

        return $result;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
