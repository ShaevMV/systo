<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\QrOrder\Application\Support\PipelineLog;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

/**
 * Связывает созданный el_ticket с существующей строкой live_tickets по номеру (setInBazaLive).
 *
 * Изолированно от основного pipeline (свои ретраи): setInBazaLive кидает, если строки live_tickets
 * с таким номером ещё нет — задача ретраится (строка может появиться). Идемпотентно: повторная
 * привязка того же el_ticket_id к тому же номеру ничего не ломает.
 */
final class LinkLiveTicketJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        private string $ticketId,
        private int $number,
    ) {}

    public function handle(TicketsRepositoryInterface $ticketsRepository): void
    {
        $log = PipelineLog::logger();

        try {
            $ticketsRepository->setInBazaLive($this->number, new Uuid($this->ticketId));

            $log->info('link_live.ok', ['ticket_id' => $this->ticketId, 'number' => $this->number]);
        } catch (Throwable $e) {
            $log->error('link_live.fail', [
                'ticket_id' => $this->ticketId,
                'number' => $this->number,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
