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
        private ?Uuid $ticketTypeId,
        private ?Uuid $festivalId = null,
    ) {
    }

    public function build(FestivalService $festivalService): static
    {
        if ($this->ticketTypeId !== null) {
            $festivalName = $festivalService->getFestivalNameByTicketType($this->ticketTypeId);
        } elseif ($this->festivalId !== null) {
            $festivalName = \App\Models\Festival\FestivalModel::query()
                ->whereId($this->festivalId->value())
                ->value('name') ?? '';
        } else {
            $festivalName = '';
        }

        $this->subject('Изменены данные вашего заказа на ' . $festivalName);

        return $this->view('email.orderToChangeTicket', [
            'changes' => $this->changes,
        ]);
    }
}
