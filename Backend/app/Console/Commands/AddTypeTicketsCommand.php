<?php

namespace App\Console\Commands;

use Database\Seeders\TypeTicketsGroupSeeder;
use Illuminate\Console\Command;

class AddTypeTicketsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:type';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Добавить тип билета';

    /**
     * Execute the console command.
     *
     * @param TypeTicketsGroupSeeder $groupSeeder
     * @return int
     */
    public function handle(
        TypeTicketsGroupSeeder $groupSeeder
    ): int
    {
        $groupSeeder->run();

        return Command::SUCCESS;
    }
}
