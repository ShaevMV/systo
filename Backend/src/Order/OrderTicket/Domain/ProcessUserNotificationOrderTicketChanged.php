<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use App\Mail\OrderToChangeTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;
use Shared\Domain\Bus\EventJobs\DomainEvent;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Service\FestivalService;

class ProcessUserNotificationOrderTicketChanged implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param array $changes [{oldName: string, newName: string}]
     */
    public function __construct(
        private string $email,
        private array  $changes,
        private ?Uuid  $ticketTypeId,
        private ?Uuid  $festivalId = null,
    ) {
    }

    public function handle(): void
    {
        Mail::to($this->email)->send(new OrderToChangeTicket(
            $this->changes,
            $this->ticketTypeId,
            $this->festivalId,
        ));
    }
}
