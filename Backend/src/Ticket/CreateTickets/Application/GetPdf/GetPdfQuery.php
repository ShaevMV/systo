<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\GetPdf;

use Tickets\Shared\Domain\Bus\Query\Query;
use Tickets\Shared\Domain\ValueObject\Uuid;

class GetPdfQuery implements Query
{
    public function __construct(
        private Uuid $orderId,
    ) {
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }
}
