<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use App\Mail\OrderToCreate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\Order\OrderTicket\Repositories\FestivalRepositoryInterface;
use Shared\Domain\Bus\EventJobs\DomainEvent;
use Shared\Domain\ValueObject\Uuid;
use App\Mail\SecondFestival\OrderToCreate as SecondOrderToCreate;
use Tickets\Order\OrderTicket\Service\FestivalService;

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

        Mail::to($this->email)
            ->send($mail);
    }
}
