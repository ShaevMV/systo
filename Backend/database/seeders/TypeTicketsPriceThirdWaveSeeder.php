<?php

namespace Database\Seeders;

use App\Models\Ordering\InfoForOrder\TicketTypesModel;
use App\Models\Ordering\InfoForOrder\TicketTypesPriceModel;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
        TicketTypesModel::find(TypeTicketsSeeder::ID_FOR_FIRST_WAVE)
            ->ticketTypePrice()
            ->saveMany([
                new  TicketTypesPriceModel([
                    'id' => self::ID_FOR_WAVE,
                    'price' => self::PRICE_FOR_SECOND_WAVE,
                    'before_date' => Carbon::yesterday(),
                ]),
                new TicketTypesPriceModel([
                    'id' => Uuid::random()->value(),
                    'price' => 4600,
                    'before_date' => (new Carbon())->subDays(1),
                ])
            ]);

        TicketTypesModel::find(TypeTicketsSeeder::ID_FOR_REGIONS)
            ->ticketTypePrice()
            ->saveMany([
                new  TicketTypesPriceModel([
                    'id' => self::ID_FOR_REGIONS,
                    'price' => self::PRICE_FOR_SECOND_WAVE,
                    'before_date' => Carbon::yesterday(),
                ]),
            ]);
    }
}
