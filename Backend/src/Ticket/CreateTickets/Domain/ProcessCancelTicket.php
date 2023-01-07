<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Domain;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;
use Tickets\Shared\Domain\Bus\EventJobs\DomainEvent;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Application\TicketApplication;

class ProcessCancelTicket implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        private Uuid $orderId,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function handle(
        TicketApplication $application
    ): void {
        $application->cancelTicket($this->orderId);
    }
}
