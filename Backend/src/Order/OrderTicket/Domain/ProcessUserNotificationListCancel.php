<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use App\Mail\OrderListCancel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\Bus\EventJobs\DomainEvent;
use Shared\Domain\ValueObject\Uuid;
use Tickets\EmailDelivery\Application\MailDispatcher;
use Tickets\EmailDelivery\Application\EmailContext;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\History\Domain\ActorType;

class ProcessUserNotificationListCancel implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $email,
        private Uuid   $festivalId,
    ) {
    }

    public function handle(): void
    {
        app(MailDispatcher::class)->send(
            EmailEvent::LIST_CANCEL,
            new EmailContext(
                recipient: $this->email,
                festivalId: $this->festivalId->value(),
                orderType: 'list',
                source: 'org_event',
                actorType: ActorType::SYSTEM,
            ),
            new OrderListCancel($this->festivalId),
        );
    }
}
