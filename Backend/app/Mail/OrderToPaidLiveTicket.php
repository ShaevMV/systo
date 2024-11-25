<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Service\FestivalService;

class OrderToPaidLiveTicket extends Mailable
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
        ini_set('memory_limit', '-1');
        $festivalName = $festivalService->getFestivalNameByTicketType($this->ticketTypeId);

        $this->subject('Ваш оргвзнос на Систо '.date('Y').' подтверждён');
        $mail = $this->view('email.orderToPaidLiveTicket',[
            'festivalName' => $festivalName
        ]);

        return $mail;
    }
}
