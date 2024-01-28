<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;

class OrderToLiveTicketIssued extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct()
    {
        $this->subject('Ваш электронный оргвзнос на ' . FestivalHelper::getNameFestival() . ' выдана живой билет');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->view('email.orderToLiveTicketIssued');
    }
}
