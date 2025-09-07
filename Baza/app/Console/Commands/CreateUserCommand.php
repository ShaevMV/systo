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
                "name" => "Катя_Арчи",
                "email" => "Katya_Archi@spaceofjoy.ru",
                "password" => "znaytvse37",
            ],
            [
                "name" => "Марина_фауст",
                "email" => "Marina_faust@spaceofjoy.ru",
                "password" => "znaytvse36",
            ],
            [
                "name" => "Никита_фауст",
                "email" => "Nikita_faust@spaceofjoy.ru",
                "password" => "znaytvse35",
            ],
            [
                "name" => "Саша_ядя",
                "email" => "Sasha_poison@spaceofjoy.ru",
                "password" => "znaytvse34",
            ],
            [
                "name" => "Маша_ядя",
                "email" => "Masha_yadya@spaceofjoy.ru",
                "password" => "znaytvse33",
            ],
            [
                "name" => "Аля фауст",
                "email" => "Alla Faust@spaceofjoy.ru",
                "password" => "znaytvse32",
            ],
            [
                "name" => "Алексей",
                "email" => "Alexey@spaceofjoy.ru",
                "password" => "znaytvse31",
            ],
            [
                "name" => "Джордж_ядя",
                "email" => "George_poison@spaceofjoy.ru",
                "password" => "znaytvse30",
            ],
            [
                "name" => "Цукерок_мария",
                "email" => "Tsukerok_Maria@spaceofjoy.ru",
                "password" => "znaytvse29",
            ],
        ];

        $repository->createList($users);
        $this->error(implode($users));

        return Command::SUCCESS;
    }
}
