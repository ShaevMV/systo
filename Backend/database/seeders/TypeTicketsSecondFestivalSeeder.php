<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;

class TypeTicketsSecondFestivalSeeder extends Seeder
{
    public const TYPE_TICKET_FOR_SECOND_FESTIVAL = '222abc0c-fc8e-4a1d-a4b0-d345cafa0923';
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('ticket_type')->insert([
            'id' => self::TYPE_TICKET_FOR_SECOND_FESTIVAL,
            'name' => 'Оргвзнос на Систо-Осень 2023',
            'price' => 3500,
            'groupLimit' => null,
            'sort' => 1,
            'festival_id' => FestivalHelper::UUID_SECOND_FESTIVAL,
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);
    }
}
