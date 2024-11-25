<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use App\Mail\OrderToPaidLiveTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;
use Shared\Domain\ValueObject\Uuid;
use Shared\Domain\Bus\EventJobs\DomainEvent;

class ProcessUserNotificationOrderPaidLiveTicket implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $email,
        private Uuid $ticketTypeId,
    )
    {
    }

    public function handle(): void
    {
        Mail::to($this->email)->send(new OrderToPaidLiveTicket(
            $this->ticketTypeId
        ));
    }
}
