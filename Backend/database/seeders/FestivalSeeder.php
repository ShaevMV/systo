<?php

namespace Database\Seeders;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Seeder;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;

class FestivalSeeder extends Seeder
{
    public const ID_FOR_2023_FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b2';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('festivals')->insert([
            'id' => FestivalHelper::UUID_FESTIVAL,
            'name'=> 'Весна',
            'year' => 2023,
            'active' => false,
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);


        DB::table('festivals')->insert([
            'id' => FestivalHelper::UUID_SECOND_FESTIVAL,
            'name'=> 'Осень',
            'year' => 2023,
            'active' => true,
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);
    }
}
