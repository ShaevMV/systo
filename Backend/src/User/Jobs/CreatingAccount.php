<?php

namespace Tickets\User\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Nette\Utils\JsonException;
use Tickets\User\Domain\AccountNewCreatingDomainEvent;

class CreatingAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private AccountNewCreatingDomainEvent $domainEvent,
    ) {
    }

    /**
     * @throws JsonException
     */
    public function handle(): void
    {
        Log::write(1, $this->domainEvent->toJson());
    }
}
