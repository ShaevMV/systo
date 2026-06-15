<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\Order\OrderTicket\Service\FestivalService;

class OrderToDifficultiesArose extends Mailable
{
    use Queueable, SerializesModels, \App\Mail\Concerns\RendersDbTemplate;

    public function __construct(
        private string $comment,
        private Uuid $ticketTypeId,
    ){

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

        $this->subject('Возникли трудности с подтверждением оргвзноса на ' . $festivalName);

        return $this->renderDbOrView('orderToDifficultiesArose', [
            'comment' => $this->comment,
        ]);
    }
}
