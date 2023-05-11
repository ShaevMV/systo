<?php
namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Admin Admin',
            'email' => 'admin@admin.ru',
            'is_admin' => true,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->insert([
            'id' => 2,
            'name' => 'Митрофан',
            'email' => 'Mitrofan@spaceofjoy.ru',
            'is_admin' => true,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->insert([
            'id' => 3,
            'name' => 'Юля Рахлина',
            'email' => 'YulyaRahlina@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('users')->insert([
            'id' => 4,
            'name' => 'Костя Ихти',
            'email' => 'KostyaIhti@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->insert([
            'id' => 5,
            'name' => 'Женя Филиппова',
            'email' => 'ZhenyaFilippova@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->insert([
            'id' => 6,
            'name' => 'Денис Арчи',
            'email' => 'DenisArchi@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->insert([
            'id' => 7,
            'name' => 'Костя',
            'email' => 'Kostya@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('users')->insert([
            'id' => 8,
            'name' => 'Лера',
            'email' => 'Lera@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('users')->insert([
            'id' => 9,
            'name' => 'Инфоцентр 1',
            'email' => 'Infocentr1@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('users')->insert([
            'id' => 10,
            'name' => 'Арчи',
            'email' => 'Archi@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);


        DB::table('users')->insert([
            'id' => 11,
            'name' => 'Головин',
            'email' => 'Golovin@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('users')->insert([
            'id' => 12,
            'name' => 'Ядя',
            'email' => 'Yadya@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->insert([
            'id' => 13,
            'name' => 'Свят',
            'email' => 'Svyat@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->insert([
            'id' => 14,
            'name' => 'Саша Свят',
            'email' => 'SashaSvyat@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->insert([
            'id' => 15,
            'name' => 'Антон Тульский',
            'email' => 'AntonTulskij@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->insert([
            'id' => 16,
            'name' => 'Катя Бут',
            'email' => 'KatyaBut@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->insert([
            'id' => 17,
            'name' => 'Фауст',
            'email' => 'Faust@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->insert([
            'id' => 18,
            'name' => 'Фауст Алена',
            'email' => 'FaustAlena@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->insert([
            'id' => 19,
            'name' => 'Тома КПП',
            'email' => 'TomaKPP@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->insert([
            'id' => 20,
            'name' => 'Егор',
            'email' => 'Egor@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);



        DB::table('users')->insert([
            'id' => 21,
            'name' => 'Алиса Егор',
            'email' => 'AlisaEgor@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('users')->insert([
            'id' => 22,
            'name' => 'Света Мутабор',
            'email' => 'SvetaMutabor@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('users')->insert([
            'id' => 23,
            'name' => 'Катя Жарова',
            'email' => 'KatyaZharova@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('users')->insert([
            'id' => 24,
            'name' => 'Лукич',
            'email' => 'Lukich@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->insert([
            'id' => 25,
            'name' => 'Гвалди',
            'email' => 'Gvaldi@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->insert([
            'id' => 26,
            'name' => 'Саша Фауст',
            'email' => 'SashaFaust@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->insert([
            'id' => 27,
            'name' => 'Настя АЛИСА',
            'email' => 'NastyaALISA@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->insert([
            'id' => 28,
            'name' => 'Оля Ваня',
            'email' => 'OlyaVanya@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->insert([
            'id' => 29,
            'name' => 'Ваня Оля',
            'email' => 'VanyaOlya@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);


        DB::table('users')->insert([
            'id' => 30,
            'name' => 'Инфоцентр 2',
            'email' => 'Infocentr2@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->insert([
            'id' => 31,
            'name' => 'Инфоцентр 3',
            'email' => 'Infocentr3@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('users')->insert([
            'id' => 32,
            'name' => 'Инфоцентр 4',
            'email' => 'Infocentr4@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('users')->insert([
            'id' => 33,
            'name' => 'Охрана 1',
            'email' => 'Ohrana1@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('users')->insert([
            'id' => 34,
            'name' => 'Охрана 2',
            'email' => 'Ohrana2@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('users')->insert([
            'id' => 35,
            'name' => 'Охрана 3',
            'email' => 'Ohrana3@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('users')->insert([
            'id' => 36,
            'name' => 'Охрана 4',
            'email' => 'Ohrana4@spaceofjoy.ru',
            'is_admin' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
