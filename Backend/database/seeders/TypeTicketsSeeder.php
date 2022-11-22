<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Tickets\Shared\Domain\ValueObject\Uuid;

class TypeTicketsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('ticket_type')->insert([
            'id' => Uuid::random()->value(),
            'name' => 'Первая волна',
            'price' => '1000'
        ]);
        DB::table('ticket_type')->insert([
            'id' => Uuid::random()->value(),
            'name' => 'Для регионов',
            'price' => '900'
        ]);
        DB::table('ticket_type')->insert([
            'id' => Uuid::random()->value(),
            'name' => 'ЗА ЛЮБОВЬ',
            'price' => '1700',
            'groupLimit' => 2
        ]);
    }
}
