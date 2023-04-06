<?php

namespace App\Mail;

use App\Services\CreatingQrCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderShippedList extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var int[]
     */
    public $ids;
    private string $email;
    private string $project;

    /**
     * Create a new message instance.
     *
     * @param int[] $ids
     */
    public function __construct(array $ids, string $email, string $project)
    {
        $this->ids = $ids;
        $this->email = $email;
        $this->project = $project;
        $this->subject('Ваше участие в Solar Systo Togathering '.date('Y').' подтверждено ');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(
        CreatingQrCodeService $service
    ): OrderShippedList
    {
        $mail = $this->from('ticket@solarsysto.ru', 'solarsysto')->view('emails.orders.orderListToPaid');

        foreach ($this->ids as $id => $name) {
            $contents = $service->createPdf($id, $name, $this->email, $this->project);
            $mail->attachData($contents->output(), 'Билет ' . $name . '.pdf');
        }

        return $mail;
    }
}
