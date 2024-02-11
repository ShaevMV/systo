<?php

namespace Database\Seeders;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Seeder;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;

class FestivalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('festivals')->insert([
            'id' => FestivalHelper::UUID_FESTIVAL,
            'name' => 'Solar Systo Togathering',
            'year' => date('Y'),
            'view' => 'pdf',
            'active' => false,
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);

        DB::table('festivals')->insert([
            'id' => FestivalHelper::UUID_SECOND_FESTIVAL,
            'name' => 'Систо-Осень',
            'view' => 'pdf2',
            'year' => date('Y'),
            'active' => true,
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);
    }
}
