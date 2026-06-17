<?php

namespace Tickets\Order\OrderTicket\Domain;

use App\Mail\OrderToDifficultiesArose;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\Bus\EventJobs\DomainEvent;
use Shared\Domain\ValueObject\Uuid;
use Tickets\EmailDelivery\Application\EmailContext;
use Tickets\EmailDelivery\Application\MailDispatcher;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\History\Domain\ActorType;

class ProcessUserNotificationOrderDifficultiesArose implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Uuid $orderId,
        private string $email,
        private string $comment,
        private Uuid $ticketTypeId,
    ) {
    }

    public function handle(): void
    {
        app(MailDispatcher::class)->send(
            EmailEvent::ORDER_DIFFICULTIES,
            new EmailContext(
                recipient: $this->email,
                ticketTypeId: $this->ticketTypeId->value(),
                source: 'org_event',
                actorType: ActorType::SYSTEM,
                aggregateType: 'order_ticket',
                aggregateId: $this->orderId->value(),
            ),
            new OrderToDifficultiesArose(
                $this->comment,
                $this->ticketTypeId
            ),
        );
    }
}
