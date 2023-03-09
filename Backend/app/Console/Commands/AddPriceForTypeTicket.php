<?php

namespace App\Console\Commands;

use Database\Seeders\TypeTicketsPriceSeeder;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class AddPriceForTypeTicket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:price';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Добавить цены';

    public function handle(
       TypeTicketsPriceSeeder $typeTicketsPriceSeeder
    ): int
    {
        $typeTicketsPriceSeeder->run();
        return CommandAlias::SUCCESS;
    }
}
