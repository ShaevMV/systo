<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tickets\Shared\Domain\ValueObject\Uuid;

class UserSeeder extends Seeder
{
    public const ID_FOR_ADMIN_UUID = 'b9df62af-252a-4890-afd7-73c2a356c259';
    public const ID_FOR_USER_UUID = 'b9df62af-252a-4890-afd7-73c2a356c260';
    public const EMAIL_USER = 'user@user.ru';
    public const EMAIL_ADMIN = 'admin@admin.ru';

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
            'email' => self::EMAIL_ADMIN,
            'password' => Hash::make('password'),
            'is_admin' => true,
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);

        DB::table('users')->insert([
            'id' => self::ID_FOR_USER_UUID,
            'name' => 'user',
            'email' => self::EMAIL_USER,
            'password' => Hash::make('password'),
            'is_admin' => false,
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);
    }
}
