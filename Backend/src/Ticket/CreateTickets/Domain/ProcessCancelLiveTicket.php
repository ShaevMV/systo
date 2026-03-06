<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Domain;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;
use Shared\Domain\Bus\EventJobs\DomainEvent;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Ticket\CreateTickets\Application\PushTicket;
use Tickets\Ticket\CreateTickets\Application\TicketApplication;

class ProcessCancelLiveTicket implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param Uuid $orderId
     * @param GuestsDto[] $guest
     */
    public function __construct(
        private Uuid $orderId,
        private array $guest,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function handle(
        TicketApplication $application,
        PushTicket $pushTicket,
    ): void {
        $application->cancelTicket($this->orderId);

        $pushTicket->pushByOrderId($this->orderId);

        foreach ($this->guest as $item) {
            if($item->getNumber()) {
                Log::info('Отмена живого билета заказ ' . $this->orderId->value(), $item->toArray());
                $pushTicket->pushTicketLive($item['number']);
            }
        }
    }
}
