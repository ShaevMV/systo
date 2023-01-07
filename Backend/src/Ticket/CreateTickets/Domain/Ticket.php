<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Domain;

use Tickets\Shared\Domain\Aggregate\AggregateRoot;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

class Ticket extends AggregateRoot
{
    private Uuid $aggregateId;

    public function __construct(
        private Uuid $orderId,
        private string $name,
        ?Uuid $aggregateId = null,
    ) {
        if (is_null($aggregateId)) {
            $this->aggregateId = Uuid::random();
        } else {
            $this->aggregateId = $aggregateId;
        }
    }

    public static function newTicket(Uuid $orderId, string $quest): self
    {
        $result = new self($orderId, $quest);

        $result->record(new ProcessCreatingQRCode($result->aggregateId));

        return $result;
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAggregateId(): Uuid
    {
        return $this->aggregateId;
    }
}
