<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\Order\OrderTicket\Service\FestivalService;

class OrderToLiveTicketIssued extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private Uuid $ticketTypeId
    )
    {
        $this->subject('Статус вашего оргвзноса изменен на "Выдан живой билет"');
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
