<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InviteLink extends Mailable
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
        $this->subject('Ссылка на оплату оргвзноса Solar Systo Togathering');

        return $this->view('email.invate', [
            'link' => $this->link,
        ]);
    }

}
