<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;

/**
 * Сидер фестивалей.
 *
 * Создаёт 2 фестиваля для тестирования фильтров «по фестивалю» —
 * чтобы можно было проверять сценарии разных фестивалей на staging.
 *
 * Идемпотентен через `upsert` — повторный запуск не даёт duplicate key error.
 */
class FestivalSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('festivals')->upsert([
            [
                'id' => FestivalHelper::UUID_FESTIVAL,
                'name' => 'Solar Systo Togathering',
                'year' => (int) $now->format('Y'),
                'view' => 'pdf',
                'active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => FestivalHelper::UUID_SECOND_FESTIVAL,
                'name' => 'Систо-Осень',
                'year' => (int) $now->format('Y'),
                'view' => 'pdf2',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['id'], ['name', 'year', 'view', 'active', 'updated_at']);
    }
}
