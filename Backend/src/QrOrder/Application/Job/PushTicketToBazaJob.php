<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Tickets\QrOrder\Application\Support\PipelineLog;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

/**
 * Запись одного билета qr-заказа в Baza (таблица el_tickets) — изолированно от основного pipeline.
 *
 * Зачем отдельная задача: сбой шины Baza не должен отменять уже созданный билет/письмо и не
 * должен приводить к повторной выдаче (дублям). setInBaza идемпотентен (upsert по uuid), поэтому
 * у задачи свои ретраи (tries=3) — Baza доедет, когда поднимется, без участия основного flow.
 */
final class PushTicketToBazaJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        private TicketResponse $ticket,
    ) {
    }

    public function handle(TicketsRepositoryInterface $ticketsRepository): void
    {
        $log = PipelineLog::logger();
        $isList = $this->ticket->isList();

        // Маршрутизация как в классике (PushTicketsCommandHandler): списочный билет → spisok_tickets,
        // обычный → el_tickets. Оба идемпотентны (upsert по uuid/ticket_uuid). На false — ретрай.
        $ok = $isList
            ? $ticketsRepository->setInBazaList($this->ticket)
            : $ticketsRepository->setInBaza($this->ticket);

        if (! $ok) {
            $log->error('push_baza.fail', ['ticket_id' => $this->ticket->getId()->value(), 'list' => $isList]);

            throw new RuntimeException('Не удалось записать билет в Baza: ' . $this->ticket->getId()->value());
        }

        $log->info('push_baza.ok', [
            'ticket_id' => $this->ticket->getId()->value(),
            'kilter' => $this->ticket->getKilter(),
            'list' => $isList,
        ]);
    }
}
