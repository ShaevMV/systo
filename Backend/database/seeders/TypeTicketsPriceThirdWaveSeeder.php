<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use JsonException;
use Shared\Domain\ValueObject\Uuid;

class TypeTicketsPriceThirdWaveSeeder extends Seeder
{
    public const ID_FOR_WAVE = 'bdec45aa-06e6-45d7-8b6b-f12f0b289d79',
        ID_FOR_REGIONS = 'bdec45aa-06e6-45d7-8b6b-f12f0b289d80',
        PRICE_FOR_SECOND_WAVE = 4800,
        PRICE_FOR_SECOND_FOR_REGIONS = 4600;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('ticket_type_price')->insert([
            'id' => self::ID_FOR_WAVE,
            'ticket_type_id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE,
            'price' => self::PRICE_FOR_SECOND_WAVE,
            'before_date' => Carbon::yesterday(),
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);
        DB::table('ticket_type_price')->insert([
            'id' => self::ID_FOR_REGIONS,
            'ticket_type_id' => TypeTicketsSeeder::ID_FOR_REGIONS,
            'price' => self::PRICE_FOR_SECOND_FOR_REGIONS,
            'before_date' => Carbon::yesterday(),
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);
    }
}
