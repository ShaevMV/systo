<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Service\FestivalService;

class OrderToChangeTicket extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array $changes [{oldName: string, newName: string}]
     */
    public function __construct(
        private array $changes,
        private Uuid  $ticketTypeId,
    ) {
    }

    public function build(FestivalService $festivalService): static
    {
        $festivalName = $festivalService->getFestivalNameByTicketType($this->ticketTypeId);

        $this->subject('Изменены данные вашего заказа на ' . $festivalName);

        return $this->view('email.orderToChangeTicket', [
            'changes' => $this->changes,
        ]);
    }
}
