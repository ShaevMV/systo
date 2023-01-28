<?php

namespace Tickets\Order\OrderTicket\Domain;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Tickets\Shared\Domain\Bus\EventJobs\DomainEvent;
use Tickets\Shared\Domain\ValueObject\Uuid;

class ProcessUserNotificationOrderDifficultiesArose implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Uuid $orderId,
        private string $email,
    ) {
    }

    public function handle(): void
    {
        //TODO: реализовать отправку мыла
        Log::debug($this->email);
    }
}