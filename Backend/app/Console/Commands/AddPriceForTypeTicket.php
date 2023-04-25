<?php

namespace App\Console\Commands;

use Tickets\Shared\Domain\ValueObject\Uuid;
use Carbon\Carbon;
use Database\Seeders\TypeTicketsSeeder;
use DB;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class AddPriceForTypeTicket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:price {price1} {price2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Добавить цены';

    public function handle(): int
    {
        DB::table('ticket_type_price')->insert([
            'id' => Uuid::random()->value(),
            'ticket_type_id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE,
            'price' => $this->argument('price1'),
            'before_date' => Carbon::yesterday(),
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);
        DB::table('ticket_type_price')->insert([
            'id' =>  Uuid::random()->value(),
            'ticket_type_id' => TypeTicketsSeeder::ID_FOR_REGIONS,
            'price' => $this->argument('price2'),
            'before_date' => Carbon::yesterday(),
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);

        return CommandAlias::SUCCESS;
    }
}
