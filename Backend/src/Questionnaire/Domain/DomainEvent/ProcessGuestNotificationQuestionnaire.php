<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Domain\DomainEvent;

use App\Mail\TicketQuestionnaire;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Shared\Domain\Bus\EventJobs\DomainEvent;
use Tickets\EmailDelivery\Application\EmailContext;
use Tickets\EmailDelivery\Application\MailDispatcher;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\History\Domain\ActorType;
use Tickets\Questionnaire\Application\Questionnaire\ExistsByEmail\QuestionnaireExistsByEmailQuery;
use Tickets\Questionnaire\Application\Questionnaire\ExistsByEmail\QuestionnaireExistsByEmailQueryHandler;

class ProcessGuestNotificationQuestionnaire implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $email,
        private string $orderId,
        private string $ticketId,
    )
    {
    }

    public function handle(QuestionnaireExistsByEmailQueryHandler $handler): void
    {
        if($handler(new QuestionnaireExistsByEmailQuery($this->email))) {
            return;
        }

        $mail = new TicketQuestionnaire(
            'https://org.spaceofjoy.ru/questionnaire/guest/'. $this->orderId . '/' . $this->ticketId
        );
        Log::info('От правил письмо для анкетирования '  . $this->email,[
            $this->orderId, $this->ticketId
        ]);
        app(MailDispatcher::class)->send(
            EmailEvent::QUESTIONNAIRE,
            new EmailContext(
                recipient: $this->email,
                source: 'org_event',
                actorType: ActorType::SYSTEM,
                aggregateType: 'order_ticket',
                aggregateId: $this->orderId,
            ),
            $mail,
        );
    }
}
