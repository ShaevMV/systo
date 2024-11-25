<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\Order\OrderTicket\Service\FestivalService;

class OrderToCancel extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private Uuid $ticketTypeId,
    )
    {

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(
        FestivalService $festivalService,
    ): static
    {
        $festivalName = $festivalService->getFestivalNameByTicketType($this->ticketTypeId);

        $this->subject('Оргвзнос на ' . $festivalName . ' отменён');

        return $this->view('email.orderToCancel');
    }
}
