<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\GetPdf;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

class GetPdfQuery implements Query
{
    public function __construct(
        private Uuid $ticketId,
    ) {
    }

    public function getTicketId(): Uuid
    {
        return $this->ticketId;
    }
}
