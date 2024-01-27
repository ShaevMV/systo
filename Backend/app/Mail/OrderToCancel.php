<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderToCancel extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct()
    {
        $this->subject('Оргвзнос на Систо' . date('Y') . ' отменён');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->view('email.orderToCancel');
    }
}
