<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use App\Mail\TicketQuestionnaire;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\Bus\EventJobs\DomainEvent;

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

    public function handle(): void
    {
        $mail = new TicketQuestionnaire(
            'https://org.spaceofjoy.ru/questionnaire/'. $this->orderId . '/' . $this->ticketId
        );

        \Mail::to($this->email)
            ->send($mail);
    }
}
