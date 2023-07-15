<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Domain;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use Shared\Domain\Bus\EventJobs\DomainEvent;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Application\PushTicket;
use Tickets\Ticket\CreateTickets\Application\TicketApplication;

class ProcessCreateTicket implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Uuid  $orderId,
        private array $quests
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function handle(
        TicketApplication $application,
        PushTicket        $pushTicket,
    ): void
    {
        $tickets = $application->createList($this->orderId, $this->quests);

        foreach ($tickets as $ticket) {
            $pushTicket->pushTicket($ticket->getId());
        }
    }
}
