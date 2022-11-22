<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Tickets\Shared\Domain\ValueObject\Uuid;

class PromoCodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('promo_code')->insert([
            'id' => Uuid::random()->value(),
            'name' => 'Systo',
            'discount' => 100
        ]);
    }
}
