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

        // Проверяем активность типа анкеты привязанного к типу билета
        $questionnaireTypeActive = DB::table('ticket_type')
            ->leftJoin('questionnaire_type', 'ticket_type.questionnaire_type_id', '=', 'questionnaire_type.id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('order_ticket')
                    ->whereColumn('order_ticket.id', 'ticket_type.id')
                    ->where('order_ticket.id', $this->orderId);
            })
            ->where(function ($query) {
                $query->whereNull('questionnaire_type.id')
                    ->orWhere('questionnaire_type.active', true);
            })
            ->exists();

        // Если тип анкеты не активен, не отправляем письмо
        if (!$questionnaireTypeActive) {
            Log::info('Тип анкеты не активен, письмо не отправлено ' . $this->email);
            return;
        }

        $mail = new TicketQuestionnaire(
            'https://org.spaceofjoy.ru/questionnaire/guest/'. $this->orderId . '/' . $this->ticketId
        );
        Log::info('От правил письмо для анкетирования '  . $this->email,[
            $this->orderId, $this->ticketId
        ]);
        \Mail::to($this->email)
            ->send($mail);
    }
}
