<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use App\Mail\OrderListDifficultiesArose;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;
use Shared\Domain\Bus\EventJobs\DomainEvent;
use Shared\Domain\ValueObject\Uuid;

class ProcessUserNotificationListDifficultiesArose implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $email,
        private string $comment,
        private Uuid   $festivalId,
    ) {
    }

    public function handle(): void
    {
        Mail::to($this->email)->send(new OrderListDifficultiesArose(
            $this->comment,
            $this->festivalId,
        ));
    }
}
