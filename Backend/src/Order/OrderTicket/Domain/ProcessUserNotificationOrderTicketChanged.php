<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use App\Mail\OrderToChangeTicket;
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
use Tickets\Order\OrderTicket\Service\FestivalService;

class ProcessUserNotificationOrderTicketChanged implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param array $changes [{oldName: string, newName: string}]
     */
    public function __construct(
        private string $email,
        private array  $changes,
        private ?Uuid  $ticketTypeId,
        private ?Uuid  $festivalId = null,
    ) {
    }

    public function handle(): void
    {
        app(MailDispatcher::class)->send(
            EmailEvent::ORDER_CHANGED,
            new EmailContext(
                recipient: $this->email,
                ticketTypeId: $this->ticketTypeId?->value(),
                festivalId: $this->festivalId?->value(),
                source: 'org_event',
                actorType: ActorType::SYSTEM,
            ),
            new OrderToChangeTicket(
                $this->changes,
                $this->ticketTypeId,
                $this->festivalId,
            ),
        );
    }
}
