<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\FestivalModel;
use Illuminate\Database\Seeder;

/**
 * Бутстрап реестра фестивалей (TD-48, PR-1).
 *
 * Заводит «текущий фестиваль» (id == зашитая ранее константа, теперь
 * config('baza.default_festival_id')), чтобы система не была пустой до синка из org.
 * firstOrCreate — НЕ затирает реальные название/год, если фестиваль уже пришёл из
 * org-реплики. Идемпотентно → безопасно гонять на каждом деплое (как
 * BazaRolePermissionsSeeder). ПОДКЛЮЧЁН к DatabaseSeeder (это конфиг-бутстрап, без ПДн).
 */
class FestivalSeeder extends Seeder
{
    public function run(): void
    {
        $id = (string) config('baza.default_festival_id');
        if ($id === '') {
            return;
        }

        $year = config('baza.default_festival_year');

        FestivalModel::firstOrCreate(
            ['id' => $id],
            [
                'name' => (string) config('baza.default_festival_name', 'Текущий фестиваль'),
                'year' => $year !== null ? (int) $year : null,
                'active' => true,
                'active_for_kpp' => true,
            ]
        );
    }
}
