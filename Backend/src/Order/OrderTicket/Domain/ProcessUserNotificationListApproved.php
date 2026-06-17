<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use App\Mail\OrderListApproved;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Shared\Domain\Bus\EventJobs\DomainEvent;
use Tickets\EmailDelivery\Application\EmailContext;
use Tickets\EmailDelivery\Application\MailDispatcher;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\History\Domain\ActorType;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Domain\ValueObject\OrderGuestLine;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

/**
 * Письмо получателю заказа-списка при переходе в статус APPROVE_LIST.
 *
 * @property OrderGuestLine[] $tickets
 */
class ProcessUserNotificationListApproved implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param OrderGuestLine[] $tickets
     */
    public function __construct(
        private string $email,
        private array  $tickets,
        private Uuid   $festivalId,
        private ?Uuid  $locationId,
    ) {
    }

    public function handle(TicketsRepositoryInterface $repository): void
    {
        $result = [];

        foreach ($this->tickets as $ticket) {
            $result[] = $repository->getTicket($ticket->id);
        }

        app(MailDispatcher::class)->send(
            EmailEvent::LIST_APPROVED,
            new EmailContext(
                recipient: $this->email,
                festivalId: $this->festivalId->value(),
                orderType: 'list',
                source: 'org_event',
                actorType: ActorType::SYSTEM,
            ),
            new OrderListApproved(
                $result,
                $this->festivalId,
                $this->locationId,
            ),
        );

        Log::info('OrderListApproved отправлено: ' . $this->email);
    }
}
