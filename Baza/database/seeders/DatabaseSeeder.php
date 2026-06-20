<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
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
            // Демо стенда: поисковый индекс ticket_search (поиск без QR) — чтобы поиск по
            // ФИО/телефону/госномеру/имени ребёнка было видно вживую. Идемпотентно.
            TicketSearchTestDataSeeder::class,
        ]);
    }
}
