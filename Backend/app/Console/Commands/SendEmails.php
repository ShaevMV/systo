<?php

namespace App\Console\Commands;

use App\Models\Ordering\OrderTicketModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Nette\Utils\JsonException;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderPaid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

class SendEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Отправить билеты';

    /**
     * @throws JsonException
     */
    public function handle(
        Bus $bus
    ): int
    {
        $tickets = OrderTicketModel::where(
            OrderTicketModel::TABLE . '.status', '=', Status::PAID
        )->with('users')
            ->with('tickets')
            ->get()
            ->toArray();
        $listEmail = [];
        foreach ($tickets as $ticket) {
            $guestsDtoList = [];
            foreach ($ticket['tickets'] as $guest) {
                $guestsDtoList[] = new GuestsDto($guest['name'], new Uuid($guest['id']));
            }

            $listEmail = new ProcessUserNotificationOrderPaid(
                $ticket['users']['email'],
                $guestsDtoList
            );
        }

        $bus::chain($listEmail)
            ->dispatch();

        return Command::SUCCESS;
    }
}
