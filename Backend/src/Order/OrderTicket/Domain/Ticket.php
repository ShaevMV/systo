<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use Tickets\Shared\Domain\Aggregate\AggregateRoot;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Domain\ProcessCreatingQRCode;

final class Ticket extends AggregateRoot
{
    private Uuid $aggregateId;
    public function __construct(
        private string $name,
        ?Uuid $aggregateId = null,
    ){
        $this->aggregateId = $aggregateId ?? Uuid::random();
    }

    public static function fromState(array $data): self
    {
        $id = isset($data['id']) ? new Uuid($data['id']) : null;
        return new self(
            $data['value'],
            $id
        );
    }


    public static function createTicket(string $quest): self
    {
        $result = new self($quest);

        $result->record(new ProcessCreatingQRCode(
            $result->aggregateId,
            $result->name,
        ));

        return $result;
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
