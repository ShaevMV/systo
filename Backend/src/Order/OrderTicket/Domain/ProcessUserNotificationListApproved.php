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
use Mail;
use Shared\Domain\Bus\EventJobs\DomainEvent;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

/**
 * Письмо получателю заказа-списка при переходе в статус APPROVE_LIST.
 *
 * @property GuestsDto[] $tickets
 */
class ProcessUserNotificationListApproved implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param GuestsDto[] $tickets
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
            $result[] = $repository->getTicket($ticket->getId());
        }

        $resultMail = Mail::to($this->email)->send(new OrderListApproved(
            $result,
            $this->festivalId,
            $this->locationId,
        ));

        Log::info('OrderListApproved отправлено: ' . $this->email);
    }
}
