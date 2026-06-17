<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Domain\DomainEvent;

use App\Mail\TicketQuestionnaire;
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

class ProcessReplayNotificationQuestionnaire implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $email,
        private string $id,
    )
    {
    }

    public function handle(): void
    {
        $mail = new TicketQuestionnaire(
            'https://org.spaceofjoy.ru/questionnaire/edit/'. $this->id
        );

        app(MailDispatcher::class)->send(
            EmailEvent::QUESTIONNAIRE,
            new EmailContext(
                recipient: $this->email,
                source: 'org_event',
                actorType: ActorType::SYSTEM,
            ),
            $mail,
        );
    }
}
