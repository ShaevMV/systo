<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\Order\OrderTicket\Service\FestivalService;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Services\CreatingQrCodeService;

class OrderToPaid extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param TicketResponse[] $tickets
     */
    public function __construct(
        private array $tickets,
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
        CreatingQrCodeService $qrCodeService,
        FestivalService $festivalService,
    ): static
    {
        ini_set('memory_limit', '-1');
        $festivalName = $festivalService->getFestivalNameByTicketType($this->ticketTypeId);

        $this->subject('Ваш оргвзнос на Систо '.date('Y').' подтверждён');
        $mail = $this->view('email.orderToPaid',[
            'festivalName' => $festivalName,
        ]);

        foreach ($this->tickets as $ticket) {
            $contents = $qrCodeService->createPdf($ticket);
            $mail->attachData($contents->output(), 'Билет ' . $ticket->getName() . '.pdf');
            \Log::info('Отправлен билет на имя '. $ticket->getName());
        }

        return $mail;
    }
}
