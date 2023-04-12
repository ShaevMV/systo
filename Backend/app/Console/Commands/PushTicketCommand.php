<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nette\Utils\JsonException;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Throwable;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Application\PushTicket\PushTicket;

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
        PushTicket $pushTicket
    ): int
    {
        try {
            $uuid = $this->argument('id') ?? null ? new Uuid($this->argument('id')) : null;

            $result = $pushTicket->pushTicket($uuid);
            $this->info('Удачно отправленные ' . implode($result));
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());
            throw $throwable;
        }

        return CommandAlias::SUCCESS;
    }
}
