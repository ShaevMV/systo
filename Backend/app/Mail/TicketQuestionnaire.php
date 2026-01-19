<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketQuestionnaire extends Mailable
{
    use Queueable, SerializesModels;

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

        return $this->view('email.questionnaire', [
            'link' => $this->link,
        ]);
    }

}
