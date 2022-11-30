<?php

declare(strict_types = 1);

namespace Tickets\User\Account\Domain;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Tickets\Shared\Domain\Bus\EventJobs\DomainEvent;

class ProcessAccountNotification implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $email,
        private string $password
    ){
    }

    public function handle(): void
    {
        //TODO: реализовать отправку мыла
        Log::debug($this->email);
        Log::debug($this->password);
    }
}
