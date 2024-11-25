<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Domain;

use Shared\Domain\Aggregate\AggregateRoot;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Services\Dto\DataInfoForPdf;

class Ticket extends AggregateRoot
{
    public function __construct(
        private string $name,
        private int $kilter,
        private Uuid $aggregateId,
        private string $email,
    ) {
    }

    public static function newTicket(TicketResponse $ticketResponse): self
    {
        $result = new self(
            $ticketResponse->getName(),
            $ticketResponse->getKilter(),
            $ticketResponse->getId(),
            $ticketResponse->getEmail());

        $result->record(new ProcessCreatingQRCode($ticketResponse));

        return $result;
    }

    /**
     * @return Uuid
     */
    public function getId(): Uuid
    {
        return $this->aggregateId;
    }

}
