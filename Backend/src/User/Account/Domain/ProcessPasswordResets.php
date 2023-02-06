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
use Illuminate\Support\Facades\Hash;
use Mail;
use Tickets\Shared\Domain\Bus\EventJobs\DomainEvent;
use Tickets\Shared\Domain\ValueObject\Uuid;

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
        $token = urlencode(md5($this->user->email));

        $activationLink = url("/passwordResets/".$token);

        PasswordResets::updateOrCreate([
            'email' => $this->user->email,
            'token' => $token
        ]);

        Mail::to($this->user)->send(new UserPasswordResets($this->user, $activationLink));
    }

}
