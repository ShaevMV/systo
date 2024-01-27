<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;

class OrderToCancel extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct()
    {
        $this->subject('Оргвзнос на ' . FestivalHelper::getNameFestival() . ' отменён');
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
