<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Tickets\Shared\Domain\ValueObject\Uuid;

class TypeTicketsSeeder extends Seeder
{
    public const ID_FOR_FIRST_WAVE = '222abc0c-fc8e-4a1d-a4b0-d345cafacf95';
    public const PRICE_FOR_FIRST_WAVE = 1000;
    public const ID_FOR_REGIONS = '37c6b8d8-e01e-4bc4-b7b8-fcaa422ab25b';
    public const ID_FOR_LOVE = 'af9b0be0-a54e-4db9-b68c-454b44b58225';
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('ticket_type')->insert([
            'id' => self::ID_FOR_FIRST_WAVE,
            'name' => 'Первая волна',
            'price' => self::PRICE_FOR_FIRST_WAVE,
        ]);
        DB::table('ticket_type')->insert([
            'id' => self::ID_FOR_REGIONS,
            'name' => 'Для регионов',
            'price' => '900'
        ]);
        DB::table('ticket_type')->insert([
            'id' => self::ID_FOR_LOVE,
            'name' => 'ЗА ЛЮБОВЬ',
            'price' => '1700',
            'groupLimit' => 2
        ]);
    }
}
