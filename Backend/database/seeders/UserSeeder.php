<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tickets\Shared\Domain\ValueObject\Uuid;

class UserSeeder extends Seeder
{
    public const ID_FOR_ADMIN_UUID = 'b9df62af-252a-4890-afd7-73c2a356c259';
    public const ID_FOR_USER_UUID = 'b9df62af-252a-4890-afd7-73c2a356c260';


    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'id' => self::ID_FOR_ADMIN_UUID,
            'name' => 'admin',
            'email' => 'admin@admin.ru',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        DB::table('users')->insert([
            'id' => self::ID_FOR_USER_UUID,
            'name' => 'user',
            'email' => 'user@user.ru',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);
    }
}
