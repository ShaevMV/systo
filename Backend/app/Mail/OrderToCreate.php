<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderToCreate extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private int $kilter,
    )
    {
        $this->subject('Оргвзнос на Систо-Осень ' . date('Y'));
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->view('email.orderToCreate',[
            'kilter' => $this->kilter
        ]);
    }

}
