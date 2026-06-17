<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use App\Mail\OrderToPaidFriendly;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Domain\ValueObject\OrderGuestLine;
use Shared\Domain\Bus\EventJobs\DomainEvent;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;
use Tickets\EmailDelivery\Application\MailDispatcher;
use Tickets\EmailDelivery\Application\EmailContext;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\History\Domain\ActorType;

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
     * @param OrderGuestLine[] $tickets
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
            $result[] = $repository->getTicket($ticket->id);
        }

        app(MailDispatcher::class)->send(
            EmailEvent::ORDER_PAID_FRIENDLY,
            new EmailContext(
                recipient: $this->email,
                ticketTypeId: $this->ticketTypeId->value(),
                orderType: 'friendly',
                source: 'org_event',
                actorType: ActorType::SYSTEM,
            ),
            new OrderToPaidFriendly(
                $result,
                $this->ticketTypeId,
                $this->comment,
                $this->promocode,
            ),
        );
    }
}
