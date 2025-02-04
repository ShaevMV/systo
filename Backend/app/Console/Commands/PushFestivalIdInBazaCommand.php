<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nette\Utils\JsonException;
use Shared\Domain\ValueObject\Uuid;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Tickets\Ticket\CreateTickets\Application\PushTicket;
use Tickets\Ticket\CreateTickets\Application\PushTicket\PushTicketsCommand;
use Tickets\Ticket\CreateTickets\Application\PushTicket\PushTicketsCommandHandler;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

class PushFestivalIdInBazaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:push_festival_id {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'отправить билеты номер фестиваля';


    /**
     * @throws \Throwable
     * @throws JsonException
     */
    public function handle(
        PushTicket                 $pushTicket,
        TicketsRepositoryInterface $ticketsRepository,
        PushTicketsCommandHandler  $handler,
    ): int
    {
        try {
            $ids = $ticketsRepository->getAllTicketsId(new Uuid($this->argument('id')));
            foreach ($ids as $id) {
                $handler(new PushTicketsCommand($id));
                $this->info('Удачно отправленные ' . $id->value());
            }

        } catch (\Throwable $throwable) {
            $this->error($throwable->getMessage());
            throw $throwable;
        }

        return CommandAlias::SUCCESS;
    }
}
