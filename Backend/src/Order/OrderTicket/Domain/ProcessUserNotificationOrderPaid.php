<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use App\Mail\OrderToPaid;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;
use Tickets\Order\Shared\Dto\GuestsDto;
use Tickets\Shared\Domain\Bus\EventJobs\DomainEvent;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

class ProcessUserNotificationOrderPaid implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param string $email
     * @param GuestsDto[] $tickets
     */
    public function __construct(
        private string $email,
        private array  $tickets,
    )
    {
    }

    public function handle(
        TicketsRepositoryInterface $repository
    ): void
    {
        $result = [];

        foreach ($this->tickets as $ticket) {
            $result[] = $repository->getTicket($ticket->getId());
        }

        Mail::to($this->email)->send(new OrderToPaid(
            $result,
        ));
    }
}
