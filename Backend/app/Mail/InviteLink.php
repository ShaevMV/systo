<?php

namespace App\Mail;

use App\Mail\Concerns\RendersDbTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InviteLink extends Mailable
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
        $this->subject('Ссылка на оплату оргвзноса Solar Systo Togathering');

        // Активный DB-шаблон (Mustache) или fallback на blade email.invate.
        return $this->renderDbOrView('invate', [
            'link' => $this->link,
        ]);
    }

}
