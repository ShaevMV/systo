<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Services\CreatingQrCodeService;

class OrderToPaid extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param TicketResponse[] $tickets
     */
    public function __construct(
        private array $tickets
    )
    {
        $this->subject('Билеты на Систо-Осень ' . date('Y'));
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(
        CreatingQrCodeService $qrCodeService,
    ): static
    {
        ini_set('memory_limit', '-1');
        $mail = $this->view('email.orderToPaid');

        foreach ($this->tickets as $ticket) {
            $contents = $qrCodeService->createPdf($ticket);
            $mail->attachData($contents->output(), 'Билет ' . $ticket->getName() . '.pdf');
            \Log::info('Отправлен билет на имя '. $ticket->getName());
        }

        return $mail;
    }
}
