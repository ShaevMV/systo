<?php

namespace Database\Seeders;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Seeder;

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
            'id' => self::ID_FOR_2023_FESTIVAL,
            'year' => 2023,
            'active' => true,
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);
    }
}
