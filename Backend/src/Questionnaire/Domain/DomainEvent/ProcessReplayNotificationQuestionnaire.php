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

        \Mail::to($this->email)
            ->send($mail);
    }
}
