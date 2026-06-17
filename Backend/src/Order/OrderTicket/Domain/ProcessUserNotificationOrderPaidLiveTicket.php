<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use App\Mail\OrderToPaidLiveTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\ValueObject\Uuid;
use Shared\Domain\Bus\EventJobs\DomainEvent;
use Tickets\EmailDelivery\Application\EmailContext;
use Tickets\EmailDelivery\Application\MailDispatcher;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\History\Domain\ActorType;

class ProcessUserNotificationOrderPaidLiveTicket implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $email,
        private Uuid $ticketTypeId,
        private Uuid $typeOrPaymentId,
        private int $kilter,
    )
    {
    }

    public function handle(): void
    {
        app(MailDispatcher::class)->send(
            EmailEvent::ORDER_PAID_LIVE,
            new EmailContext(
                recipient: $this->email,
                ticketTypeId: $this->ticketTypeId->value(),
                orderType: 'live',
                source: 'org_event',
                actorType: ActorType::SYSTEM,
            ),
            new OrderToPaidLiveTicket(
                $this->ticketTypeId,
                $this->typeOrPaymentId,
                $this->kilter
            ),
        );
    }
}
