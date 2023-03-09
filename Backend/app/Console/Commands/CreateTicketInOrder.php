<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;
use Tickets\Ticket\CreateTickets\Services\CreatingQrCodeService;

class CreateTicketInOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:create {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создать билет в заказе по ID';

    public function handle(
        CreatingQrCodeService $qrCodeService,
        TicketsRepositoryInterface $ticketsRepository,
    ): int
    {
        $ticket = $ticketsRepository->getTicket(new Uuid($this->argument('id')));
        $pdf = $qrCodeService->createPdf($ticket, '/newTickets/');

        $pdf->save(storage_path("app/public/tickets/{$this->argument('id')}.pdf"));
        return CommandAlias::SUCCESS;
    }
}
