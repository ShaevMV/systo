<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tickets\Shared\Domain\ValueObject\Uuid;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'id' => Uuid::random()->value(),
            'name' => 'admin',
            'email' => 'admin@admin.ru',
            'password' => Hash::make('password'),
        ]);
    }
}
