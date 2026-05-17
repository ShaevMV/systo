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
                "name" => "Охрана1",
                "email" => "Security1@spaceofjoy.ru",
                "password" => "znaytvse2",
            ],
            [
                "name" => "Охрана2",
                "email" => "Security2@spaceofjoy.ru",
                "password" => "znaytvse2",
            ],
            [
                "name" => "Охрана3",
                "email" => "Security3@spaceofjoy.ru",
                "password" => "znaytvse2",
            ],
            [
                "name" => "Охрана4",
                "email" => "Security4@spaceofjoy.ru",
                "password" => "znaytvse2",
            ],
            [
                "name" => "Инфоцентр1",
                "email" => "Infocenter1@spaceofjoy.ru",
                "password" => "znaytvse2",
            ],
            [
                "name" => "Инфоцентр2",
                "email" => "Infocenter2@spaceofjoy.ru",
                "password" => "znaytvse2",
            ],
            [
                "name" => "Инфоцентр3",
                "email" => "Infocenter3@spaceofjoy.ru",
                "password" => "znaytvse2",
            ],
            [
                "name" => "Инфоцентр4",
                "email" => "Infocenter4@spaceofjoy.ru",
                "password" => "znaytvse2",
            ],
            [
                "name" => "кп3-1",
                "email" => "kp3-1@spaceofjoy.ru",
                "password" => "znaytvse2",
            ],
            [
                "name" => "кп3-2",
                "email" => "kp3-2@spaceofjoy.ru",
                "password" => "znaytvse2",
            ],
            [
                "name" => "кп3-3",
                "email" => "kp3-3@spaceofjoy.ru",
                "password" => "znaytvse2",
            ],
            [
                "name" => "кп3-4",
                "email" => "kp3-4@spaceofjoy.ru",
                "password" => "znaytvse2",
            ],
            [
                "name" => "кп3-5",
                "email" => "kp3-5@spaceofjoy.ru",
                "password" => "znaytvse2",
            ],
            [
                "name" => "кп3-6",
                "email" => "kp3-6@spaceofjoy.ru",
                "password" => "znaytvse2",
            ],
            [
                "name" => "кп3-7",
                "email" => "kp3-7@spaceofjoy.ru",
                "password" => "znaytvse2",
            ],
            [
                "name" => "Оля_Кабанова",
                "email" => "Olya_Kabanova@spaceofjoy.ru",
                "password" => "znaytvse2",
            ],
            [
                "name" => "Лукич",
                "email" => "Lukich@spaceofjoy.ru",
                "password" => "znaytvse3",
            ],
            [
                "name" => "Пряник",
                "email" => "Gingerbread@spaceofjoy.ru",
                "password" => "znaytvse4",
            ],
            [
                "name" => "Мари_Йосики",
                "email" => "Mari_Yoshiki@spaceofjoy.ru",
                "password" => "znaytvse5",
            ],
            [
                "name" => "Тася",
                "email" => "Tasya@spaceofjoy.ru",
                "password" => "znaytvse6",
            ],
            [
                "name" => "Катя_Трансер",
                "email" => "Katya_Transer@spaceofjoy.ru",
                "password" => "znaytvse7",
            ],
            [
                "name" => "leksandra_Uskova",
                "email" => "leksandra_Uskova@spaceofjoy.ru",
                "password" => "znaytvse8",
            ],
            [
                "name" => "Арчи",
                "email" => "Archie@spaceofjoy.ru",
                "password" => "znaytvse9",
            ],
            [
                "name" => "Ядвига",
                "email" => "Jadwiga@spaceofjoy.ru",
                "password" => "znaytvse10",
            ],
            [
                "name" => "Сергей_Дервоед",
                "email" => "Sergey_Dervoed@spaceofjoy.ru",
                "password" => "znaytvse11",
            ],
            [
                "name" => "Митрофан",
                "email" => "Mitrofan@spaceofjoy.ru",
                "password" => "znaytvse12",
            ],
            [
                "name" => "мария_искра",
                "email" => "maria_spark@spaceofjoy.ru",
                "password" => "znaytvse13",
            ],
            [
                "name" => "Свят_Митраван",
                "email" => "Saint_Mitravan@spaceofjoy.ru",
                "password" => "znaytvse14",
            ],
            [
                "name" => "Катя_Арчи",
                "email" => "Katya_Archi@spaceofjoy.ru",
                "password" => "znaytvse15",
            ],
            [
                "name" => "Илья (Фауст)",
                "email" => "Iliya_faust@spaceofjoy.ru",
                "password" => "znaytvse16",
            ],
            [
                "name" => "Аня (Арчи)",
                "email" => "Anya_archie@spaceofjoy.ru",
                "password" => "znaytvse17",
            ],
            [
                "name" => "Артем (Фауст)",
                "email" => "artem_faust@spaceofjoy.ru",
                "password" => "znaytvse18",
            ],
            [
                "name" => "Шурик (Фауст)",
                "email" => "shurik_faust@spaceofjoy.ru",
                "password" => "znaytvse19",
            ],
            [
                "name" => "Элионора",
                "email" => "Eleonora@spaceofjoy.ru",
                "password" => "znaytvse20",
            ],
            [
                "name" => "Таня Каберник",
                "email" => "tanya_kabernik@spaceofjoy.ru",
                "password" => "znaytvse21",
            ],
            [
                "name" => "Альберт (Фауст)",
                "email" => "albert_faust@spaceofjoy.ru",
                "password" => "znaytvse22",
            ],
            [
                "name" => "Макс (Фауст)",
                "email" => "maks_faust@spaceofjoy.ru",
                "password" => "znaytvse23",
            ],
            [
                "name" => "Юля_Маурина",
                "email" => "Yulya_maurina@spaceofjoy.ru",
                "password" => "znaytvse24",
            ],
            [
                "name" => "Мария Краснощекова",
                "email" => "maria@spaceofjoy.ru",
                "password" => "znaytvse25",
            ],
            [
                "name" => "Митрофан",
                "email" => "Mitrofan@spaceofjoy.ru",
                "password" => "systempass12",
            ],
            [
                "name" => "Admin",
                "email" => "admin@admin.ru",
                "password" => "systempass12",
            ],
            [
                "name" => "ФАУСТ",
                "email" => "faust.prod@mail.ru",
                "password" => "systempass12",
            ],
        ];

        $repository->createList($users);


        return Command::SUCCESS;
    }
}
