<?php

namespace Database\Seeders;

use App\Models\ChangesModel;
use Illuminate\Database\Seeder;

/**
 * Тестовые данные для модуля Changes.
 *
 * Используется в Unit/Feature-тестах (например ChangesTest), не подключается
 * к DatabaseSeeder для прода. Создаёт одну открытую смену с user_id=1
 * (см. UsersTableSeeder — Admin Admin).
 *
 * Сценарии:
 *  - test_get_changes_id: ищет смену по user_id=1 → должна вернуть id=1
 *  - test_get_report:     генерирует отчёт по festival_id → массив со сменами
 */
class ChangesTestDataSeeder extends Seeder
{
    public const FESTIVAL_ID = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';

    public const ADMIN_USER_ID = 1;

    public function run(): void
    {
        $this->call([UsersTableSeeder::class]);

        ChangesModel::factory()
            ->forUsers([self::ADMIN_USER_ID])
            ->create([
                'count_el_tickets' => 5,
                'count_live_tickets' => 2,
                'festival_id' => self::FESTIVAL_ID,
            ]);
    }
}
