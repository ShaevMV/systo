<?php

namespace App\Mail;

use App\Services\CreatingQrCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderShipped extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var int[]
     */
    public $ids;
    private string $email;

    /**
     * Create a new message instance.
     *
     * @param int[] $ids
     */
    public function __construct(array $ids, string $email)
    {
        $this->ids = $ids;
        $this->email = $email;
        $this->subject('Билеты на Solar Systo Togathering ' . date('Y'));
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(
        CreatingQrCodeService $service
    ): OrderShipped
    {
        $mail = $this->from('ticket@solarsysto.ru', 'solarsysto')->view('emails.orders.orderToPaid');

        foreach ($this->ids as $id => $name) {
            $contents = $service->createPdf($id, $name, $this->email);
            $mail->attachData($contents->output(), 'Билет ' . $name . '.pdf');
        }

        return $mail;
    }
}
