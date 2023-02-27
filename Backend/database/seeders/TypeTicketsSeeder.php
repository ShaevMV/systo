<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Tickets\Shared\Domain\ValueObject\Uuid;

class TypeTicketsSeeder extends Seeder
{
    public const ID_FOR_FIRST_WAVE = '222abc0c-fc8e-4a1d-a4b0-d345cafacf95';
    public const DEFAULT_PRICE = 3800;
    public const ID_FOR_REGIONS = '37c6b8d8-e01e-4bc4-b7b8-fcaa422ab25b';
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('ticket_type')->insert([
            'id' => self::ID_FOR_FIRST_WAVE,
            'name' => 'Оргвзнос',
            'price' => self::DEFAULT_PRICE,
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);
        DB::table('ticket_type')->insert([
            'id' => self::ID_FOR_REGIONS,
            'name' => 'Оргвзнос для регионов',
            'price' => '3600',
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);
    }
}
