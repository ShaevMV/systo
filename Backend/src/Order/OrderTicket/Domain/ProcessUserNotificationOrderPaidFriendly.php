<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use App\Mail\OrderToPaidFriendly;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Mail;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Shared\Domain\Bus\EventJobs\DomainEvent;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

/**
 * Domain Event для отправки email при оплате Friendly-заказа.
 *
 * Отличается от ProcessUserNotificationOrderPaid тем что:
 * - Использует OrderToPaidFriendly Mailable (отдельные шаблоны без /myOrders)
 * - У гостей friendly-заказов нет личного кабинета
 *
 * Принцип Open/Closed (SOLID):
 * - ProcessUserNotificationOrderPaid ЗАКРЫТ для модификации
 * - ProcessUserNotificationOrderPaidFriendly ОТКРЫТ как расширение
 */
class ProcessUserNotificationOrderPaidFriendly implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param GuestsDto[] $tickets
     */
    public function __construct(
        private string $email,
        private array  $tickets,
        private Uuid $ticketTypeId,
        private ?string $comment = null,
        private ?string $promocode = null,
    )
    {
    }

    public function handle(
        TicketsRepositoryInterface $repository
    ): void
    {
        $result = [];

        foreach ($this->tickets as $ticket) {
            $result[] = $repository->getTicket($ticket->getId());
        }

        $resultMail = Mail::to($this->email)->send(new OrderToPaidFriendly(
            $result,
            $this->ticketTypeId,
            $this->comment,
            $this->promocode,
        ));

        Log::info($resultMail->getDebug());
    }
}
