<?php

namespace Database\Seeders;

use App\Models\Ordering\InfoForOrder\TicketTypesModel;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeTicketsGroupSeeder extends Seeder
{
    public const ID_FOR_FIRST_WAVE = '222abc0c-fc8e-4a1d-a4b0-d345cafacf77';
    public const DEFAULT_PRICE = 24000;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table(TicketTypesModel::TABLE)->insert([
            'id' => self::ID_FOR_FIRST_WAVE,
            'name' => 'Оргвзнос на День Победы на 5-х человек',
            'price' => self::DEFAULT_PRICE,
            'groupLimit' => 5,
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);
    }
}
