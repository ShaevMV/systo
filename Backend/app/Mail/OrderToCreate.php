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

    }

    public function build(): static
    {
        $this->subject('Оргвзнос на '. $this->festivalName);

        return $this->view('email.orderToCreate', [
            'kilter' => $this->kilter,
            'festivalShortName' => FestivalHelper::getNameFestival(),
            'festivalName' => $this->festivalName,
        ]);
    }

}
