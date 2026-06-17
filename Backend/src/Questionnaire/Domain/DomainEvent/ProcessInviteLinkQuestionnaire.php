<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Domain\DomainEvent;

use App\Mail\InviteLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\Bus\EventJobs\DomainEvent;
use Tickets\EmailDelivery\Application\MailDispatcher;
use Tickets\EmailDelivery\Application\EmailContext;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\History\Domain\ActorType;

class ProcessInviteLinkQuestionnaire implements ShouldQueue, DomainEvent
{
    const UUID_USER = '3a69674b-e062-4223-b2c2-b1a59777005c';

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $email,
    )
    {
    }

    public function handle(): void
    {
        $mail = new InviteLink(
            'https://org.spaceofjoy.ru/invite/newUser/' . self::UUID_USER
        );

        app(MailDispatcher::class)->send(
            EmailEvent::INVITE,
            new EmailContext(
                recipient: $this->email,
                source: 'org_event',
                actorType: ActorType::SYSTEM,
            ),
            $mail,
        );
    }
}
