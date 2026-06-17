<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use App\Mail\OrderToCreate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\Order\OrderTicket\Repositories\FestivalRepositoryInterface;
use Shared\Domain\Bus\EventJobs\DomainEvent;
use Shared\Domain\ValueObject\Uuid;
use App\Mail\SecondFestival\OrderToCreate as SecondOrderToCreate;
use Tickets\Order\OrderTicket\Service\FestivalService;
use Tickets\EmailDelivery\Application\MailDispatcher;
use Tickets\EmailDelivery\Application\EmailContext;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\History\Domain\ActorType;

class ProcessUserNotificationNewOrderTicket implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $email,
        private int    $kilter,
        private Uuid   $ticketTypeId,
        private Uuid   $festival,
    )
    {
    }

    public function handle(): void
    {
        $festivalService = app()->get(FestivalService::class);

        $mail = new OrderToCreate(
            $this->kilter,
            $festivalService->getFestivalNameByTicketType($this->ticketTypeId),
        );

        app(MailDispatcher::class)->send(
            EmailEvent::ORDER_CREATED,
            new EmailContext(
                recipient: $this->email,
                ticketTypeId: $this->ticketTypeId->value(),
                festivalId: $this->festival->value(),
                orderType: 'regular',
                source: 'org_event',
                actorType: ActorType::SYSTEM,
            ),
            $mail,
        );
    }
}
