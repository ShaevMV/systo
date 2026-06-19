<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Domain\DomainEvent;

use App\Mail\QuestionnaireApproved;
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

/**
 * Уведомление гостя «анкета одобрена» (событие order/анкеты при approve).
 *
 * Заменило прежнее ProcessInviteLinkQuestionnaire в Questionnaire::toApprove: то же письмо
 * (ссылка-приглашение на оплату оргвзноса), но под отдельным событием EmailEvent::QUESTIONNAIRE_APPROVED
 * — чтобы факт одобрения был виден в каталоге писем, привязке шаблонов и трекинге доставки.
 *
 * Шлётся через MailDispatcher (source = org_event) → запись в email_messages с трекингом.
 */
class ProcessQuestionnaireApprovedNotification implements ShouldQueue, DomainEvent
{
    const UUID_USER = '3a69674b-e062-4223-b2c2-b1a59777005c';

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $email,
    ) {
    }

    public function handle(): void
    {
        $mail = new QuestionnaireApproved(
            'https://org.spaceofjoy.ru/invite/newUser/' . self::UUID_USER
        );

        app(MailDispatcher::class)->send(
            EmailEvent::QUESTIONNAIRE_APPROVED,
            new EmailContext(
                recipient: $this->email,
                source: 'org_event',
                actorType: ActorType::SYSTEM,
            ),
            $mail,
        );
    }
}
