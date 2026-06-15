<?php

namespace App\Mail;

use App\Mail\Concerns\RendersDbTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketQuestionnaire extends Mailable
{
    use Queueable, SerializesModels, RendersDbTemplate;

    public function __construct(
        private string $link,
    )
    {
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        $this->subject('Анкета участника Solar Systo Togathering');

        // Активный DB-шаблон (Mustache) или fallback на blade email.questionnaire.
        return $this->renderDbOrView('questionnaire', [
            'link' => $this->link,
        ]);
    }

}
