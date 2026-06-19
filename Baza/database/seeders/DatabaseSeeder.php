<?php
namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            UsersTableSeeder::class,
            // Дефолтная матрица прав роль×действие (Ф2) — идемпотентна, без ПДн,
            // защищает от «заперли всех» при появлении не-админских ролей.
            BazaRolePermissionsSeeder::class,
        ]);
    }
}
