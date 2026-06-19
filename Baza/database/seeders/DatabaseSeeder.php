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
            // Фикстура персонала (id 1..36, дев-пароль 'secret'), идемпотентна.
            UsersTableSeeder::class,
            // Дефолтная матрица прав роль×действие (Ф2) — идемпотентна, без ПДн,
            // защищает от «заперли всех» при появлении не-админских ролей.
            BazaRolePermissionsSeeder::class,
            // Демо стенда (TD-41): раздаёт разные роли смены + открывает демо-смену,
            // чтобы Ф2 (роли/RBAC) было видно вживую. Идемпотентно.
            StagingDemoSeeder::class,
        ]);
    }
}
