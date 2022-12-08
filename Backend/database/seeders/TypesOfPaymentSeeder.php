<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Tickets\Shared\Domain\ValueObject\Uuid;

class TypesOfPaymentSeeder extends Seeder
{
    public const ID_FOR_SBER_BANK = '3fcded69-4aef-4c4a-a041-52c91e5afd63';
    public const ID_FOR_QIWI = '3fcded69-4aef-4c4a-a041-52c91e5afd80';
    public const ID_FOR_YANDEX = '3fcded69-4aef-4c4a-a041-52c91e5afd90';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('types_of_payment')->insert([
            'id' => self::ID_FOR_SBER_BANK,
            'name' => 'Карта Сбербанка 4276 5501 0313 4998',
        ]);
        DB::table('types_of_payment')->insert([
            'id' => self::ID_FOR_QIWI,
            'name' => 'Qiwi money 9789830301',
        ]);
        DB::table('types_of_payment')->insert([
            'id' => self::ID_FOR_YANDEX,
            'name' => 'ЮMoney (Яндекс.Деньги) 410012835840761',
        ]);
    }
}
