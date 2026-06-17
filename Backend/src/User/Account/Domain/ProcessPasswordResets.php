<?php

declare(strict_types=1);

namespace Tickets\User\Account\Domain;

use App\Mail\UserPasswordResets;
use App\Models\PasswordResets;
use App\Models\User;
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

class ProcessPasswordResets implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private User $user
    ) {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $token = urlencode(bin2hex(random_bytes(32)));

        $activationLink = "https://org.spaceofjoy.ru/resetPassword/".$token;

        PasswordResets::updateOrCreate([
            'email' => $this->user->email,
            'token' => $token
        ]);

        app(MailDispatcher::class)->send(
            EmailEvent::PASSWORD_RESET,
            new EmailContext(
                recipient: $this->user->email,
                source: 'org_event',
                actorType: ActorType::SYSTEM,
            ),
            new UserPasswordResets($activationLink),
        );
    }
}
