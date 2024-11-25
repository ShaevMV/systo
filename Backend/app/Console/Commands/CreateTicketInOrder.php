<?php

namespace App\Console\Commands;

use App\Models\Ordering\OrderTicketModel;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Tickets\Order\OrderTicket\Inspectors\CheckStatusChangeInspector;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
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
        $pdf = $qrCodeService->createPdf($ticket);

        $pdf->save(storage_path("app/public/tickets/{$this->argument('id')}.pdf"));
        return CommandAlias::SUCCESS;
    }
}
