<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Shared\Domain\ValueObject\Uuid;

class UserSeeder extends Seeder
{
    public const ID_FOR_ADMIN_UUID = 'b9df62af-252a-4890-afd7-73c2a356c259';
    public const ID_FOR_USER_UUID = 'b9df62af-252a-4890-afd7-73c2a356c260';
    public const EMAIL_USER = 'shaevmv@gmail.com';
    public const EMAIL_ADMIN = 'admin@spaceofjoy.ru';
    public const PASSWORD_ADMIN = 'osenosen';

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
            'phone' => '+999999999',
            'city' => 'spb',
            'email' => self::EMAIL_ADMIN,
            'password' => Hash::make(self::PASSWORD_ADMIN),
            'is_admin' => true,
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);

        DB::table('users')->insert([
            'id' => self::ID_FOR_USER_UUID,
            'name' => 'user',
            'phone' => '+999999999',
            'city' => 'spb',
            'email' => self::EMAIL_USER,
            'password' => Hash::make('password'),
            'is_admin' => false,
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);
    }
}
