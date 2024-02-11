<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\Order\OrderTicket\Service\FestivalService;
use Tickets\Ticket\CreateTickets\Services\CreatingQrCodeService;

class OrderToPaidLiveTicket extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private Uuid $ticketTypeId,
    )
    {
        $this->subject('Билеты на '. FestivalHelper::getNameFestival() . ' вы можете забрать в Лесной');
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
        $mail = $this->view('email.orderToPaidLiveTicket',[
            'festivalName' => $festivalService->getFestivalNameByTicketType($this->ticketTypeId),
        ]);

        return $mail;
    }
}
