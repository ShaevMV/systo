<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderToDifficultiesArose extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private string $comment
    ){
        $this->subject('Возникли трудности с подтверждением оргвзноса на Солар Систо ' . date('Y'));
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->view('email.orderToDifficultiesArose',[
            'comment' => $this->comment,
        ]);
    }
}
