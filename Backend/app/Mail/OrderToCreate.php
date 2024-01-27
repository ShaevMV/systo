<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;

class OrderToCreate extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private int  $kilter,
        private string $festivalName,
    )
    {
        $this->subject('Оргвзнос на '. FestivalHelper::getNameFestival());
    }

    public function build(): static
    {
        return $this->view('email.orderToCreate', [
            'kilter' => $this->kilter,
            'festivalShortName' => FestivalHelper::getNameFestival(),
            'festivalName' => $this->festivalName,
        ]);
    }

}
