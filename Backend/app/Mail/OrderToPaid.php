<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;

class OrderToPaid extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param GuestsDto[] $tickets
     */
    public function __construct(
        private array $tickets
    ){
        $this->subject('Билеты на Солар Систо '. date('Y'));
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        $mail = $this->view('email.orderToPaid');

        foreach ($this->tickets as $ticket) {
            $mail->attach(storage_path("app/public/tickets/{$ticket->getId()->value()}.pdf"));
        }

        return $mail;
    }
}
