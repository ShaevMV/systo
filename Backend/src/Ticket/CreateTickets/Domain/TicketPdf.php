<?php

namespace Tickets\Ticket\CreateTickets\Domain;

use Endroid\QrCode\Writer\Result\ResultInterface;
use Tickets\Shared\Domain\Aggregate\AggregateRoot;

class TicketPdf extends AggregateRoot
{
    public function __construct(
        private ResultInterface $QrCode,
        private int $number,
        private string $name
    ){
    }

    public static function createPdf(ResultInterface $QrCode, int $number, string $name): self
    {
        $result = new self($QrCode, $number, $name);
        $result->record();
    }
}
