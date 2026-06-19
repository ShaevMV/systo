<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Базовый набор персонала-фикстуры (dev/staging + тесты).
 *
 * Это НЕ боевой список (боевой персонал заводится перед фестивалём из
 * staff_users.php через tickets:crateUser, см. StaffUsersSeeder). Здесь —
 * фиксированные id (тесты опираются на id=1 = admin, id=3 = обычный) с дев-паролем
 * 'secret'.
 *
 * ИДЕМПОТЕНТЕН (TD-41): updateOrInsert по id → повторный прогон (force-seed на
 * стенде) обновляет, а не падает с duplicate key. role здесь НЕ задаём — роли
 * смены раздаёт демо-сидер стенда (StagingDemoSeeder), чтобы не ломать тесты,
 * которые ждут id=3 без роли (= ticketer).
 */
class UsersTableSeeder extends Seeder
{
    /**
     * @var array<int, array{0:int, 1:string, 2:string, 3:bool}>
     */
    private const USERS = [
        [1, 'Admin Admin', 'admin@admin.ru', true],
        [2, 'Митрофан', 'Mitrofan@spaceofjoy.ru', true],
        [3, 'Юля Рахлина', 'YulyaRahlina@spaceofjoy.ru', false],
        [4, 'Костя Ихти', 'KostyaIhti@spaceofjoy.ru', false],
        [5, 'Женя Филиппова', 'ZhenyaFilippova@spaceofjoy.ru', false],
        [6, 'Денис Арчи', 'DenisArchi@spaceofjoy.ru', false],
        [7, 'Костя', 'Kostya@spaceofjoy.ru', false],
        [8, 'Лера', 'Lera@spaceofjoy.ru', false],
        [9, 'Инфоцентр 1', 'Infocentr1@spaceofjoy.ru', false],
        [10, 'Арчи', 'Archi@spaceofjoy.ru', false],
        [11, 'Головин', 'Golovin@spaceofjoy.ru', false],
        [12, 'Ядя', 'Yadya@spaceofjoy.ru', false],
        [13, 'Свят', 'Svyat@spaceofjoy.ru', false],
        [14, 'Саша Свят', 'SashaSvyat@spaceofjoy.ru', false],
        [15, 'Антон Тульский', 'AntonTulskij@spaceofjoy.ru', false],
        [16, 'Катя Бут', 'KatyaBut@spaceofjoy.ru', false],
        [17, 'Фауст', 'Faust@spaceofjoy.ru', false],
        [18, 'Фауст Алена', 'FaustAlena@spaceofjoy.ru', false],
        [19, 'Тома КПП', 'TomaKPP@spaceofjoy.ru', false],
        [20, 'Егор', 'Egor@spaceofjoy.ru', false],
        [21, 'Алиса Егор', 'AlisaEgor@spaceofjoy.ru', false],
        [22, 'Света Мутабор', 'SvetaMutabor@spaceofjoy.ru', false],
        [23, 'Катя Жарова', 'KatyaZharova@spaceofjoy.ru', false],
        [24, 'Лукич', 'Lukich@spaceofjoy.ru', false],
        [25, 'Гвалди', 'Gvaldi@spaceofjoy.ru', false],
        [26, 'Саша Фауст', 'SashaFaust@spaceofjoy.ru', false],
        [27, 'Настя АЛИСА', 'NastyaALISA@spaceofjoy.ru', false],
        [28, 'Оля Ваня', 'OlyaVanya@spaceofjoy.ru', false],
        [29, 'Ваня Оля', 'VanyaOlya@spaceofjoy.ru', false],
        [30, 'Инфоцентр 2', 'Infocentr2@spaceofjoy.ru', false],
        [31, 'Инфоцентр 3', 'Infocentr3@spaceofjoy.ru', false],
        [32, 'Инфоцентр 4', 'Infocentr4@spaceofjoy.ru', false],
        [33, 'Охрана 1', 'Ohrana1@spaceofjoy.ru', false],
        [34, 'Охрана 2', 'Ohrana2@spaceofjoy.ru', false],
        [35, 'Охрана 3', 'Ohrana3@spaceofjoy.ru', false],
        [36, 'Охрана 4', 'Ohrana4@spaceofjoy.ru', false],
    ];

    public function run(): void
    {
        $now = now();

        foreach (self::USERS as [$id, $name, $email, $isAdmin]) {
            // updateOrInsert по id → идемпотентно (повторный прогон не падает на дубле)
            DB::table('users')->updateOrInsert(
                ['id' => $id],
                [
                    'name'              => $name,
                    'email'             => $email,
                    'is_admin'          => $isAdmin,
                    'email_verified_at' => $now,
                    'password'          => Hash::make('secret'),
                    'updated_at'        => $now,
                    'created_at'        => $now,
                ]
            );
        }
    }
}
