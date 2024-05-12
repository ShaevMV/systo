<?php

namespace App\Mail;

use Shared\Services\CreatingQrCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderLiveShipped extends Mailable
{
    use Queueable, SerializesModels;

    private string $email;

    /**
     * Create a new message instance.
     */
    public function __construct(string $email)
    {
        $this->email = trim($email);
        $this->subject('Живой билет на Solar Systo Togathering ' . date('Y') . ' зарегистрирован');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(
        CreatingQrCodeService $service
    ): OrderLiveShipped
    {
        $mail = $this->from('ticket@solarsysto.ru', 'solarsysto')
            ->view('emails.orders.orderToPaidLive');


        return $mail;
    }
}
