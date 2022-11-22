<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Tickets\Shared\Domain\ValueObject\Uuid;

class TypesOfPaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('types_of_payment')->insert([
            'id' => Uuid::random()->value(),
            'name' => 'Карта Сбербанка 4276 5501 0313 4998',
        ]);
        DB::table('types_of_payment')->insert([
            'id' => Uuid::random()->value(),
            'name' => 'Qiwi money 9789830301',
        ]);
        DB::table('types_of_payment')->insert([
            'id' => Uuid::random()->value(),
            'name' => 'ЮMoney (Яндекс.Деньги) 410012835840761',
        ]);
    }
}
