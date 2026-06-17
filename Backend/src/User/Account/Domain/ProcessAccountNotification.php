<?php

declare(strict_types = 1);

namespace Tickets\User\Account\Domain;

use App\Mail\CreateUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\Bus\EventJobs\DomainEvent;
use Tickets\EmailDelivery\Application\EmailContext;
use Tickets\EmailDelivery\Application\MailDispatcher;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\History\Domain\ActorType;

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
        app(MailDispatcher::class)->send(
            EmailEvent::USER_REGISTERED,
            new EmailContext(
                recipient: $this->email,
                source: 'org_event',
                actorType: ActorType::SYSTEM,
            ),
            new CreateUser(
                $this->email,
                $this->password,
            ),
        );
    }
}
