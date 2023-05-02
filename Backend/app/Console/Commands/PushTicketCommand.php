<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nette\Utils\JsonException;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Throwable;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Application\PushTicket;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

class PushTicketCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:push {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'отправить билеты';


    /**
     * @throws Throwable
     * @throws JsonException
     */
    public function handle(
        PushTicket                 $pushTicket,
        TicketsRepositoryInterface $ticketsRepository
    ): int
    {
        try {
            $uuid = $this->argument('id') ?? null ? new Uuid($this->argument('id')) : null;
            if (is_null($uuid)) {
                $ids = $ticketsRepository->getAllTicketsId();
                foreach ($ids as $id) {
                    $pushTicket->pushTicket($id);
                    $this->info('Удачно отправленные ' . $id->value());
                }
            } else {
                $pushTicket->pushTicket($uuid);
                $this->info('Удачно отправленные ' . $uuid->value());
            }


        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());
            throw $throwable;
        }

        return CommandAlias::SUCCESS;
    }
}
