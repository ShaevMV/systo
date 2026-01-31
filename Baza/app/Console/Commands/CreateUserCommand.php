<?php

namespace App\Console\Commands;

use App\Models\LiveTicketModel;
use Baza\Tickets\Repositories\LiveTicketRepositoryInterface;
use Baza\Tickets\Repositories\UserRepositoryInterface;
use Illuminate\Console\Command;

class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:crateUser';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создать пользователей';

    /**
     * Execute the console command.
     *
     * @param UserRepositoryInterface $repository
     * @return int
     */
    public function handle(UserRepositoryInterface $repository): int
    {
        $users = [
            [
                "name" => "Tulskiy",
                "email" => "Tulskiy@spaceofjoy.ru",
                "password" => "znaytvse57",
            ],
        ];

        $repository->createList($users);


        return Command::SUCCESS;
    }
}
