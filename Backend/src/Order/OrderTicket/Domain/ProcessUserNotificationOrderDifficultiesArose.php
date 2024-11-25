<?php

namespace Tickets\Order\OrderTicket\Domain;

use App\Mail\OrderToDifficultiesArose;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Mail;
use Shared\Domain\Bus\EventJobs\DomainEvent;
use Shared\Domain\ValueObject\Uuid;

class ProcessUserNotificationOrderDifficultiesArose implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Uuid $orderId,
        private string $email,
        private string $comment,
        private Uuid $ticketTypeId,
    ) {
    }

    public function handle(): void
    {
        Mail::to($this->email)
            ->send(new OrderToDifficultiesArose(
                $this->comment,
                $this->ticketTypeId
            ));
    }
}
