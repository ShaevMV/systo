<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use JsonException;
use Tickets\Shared\Domain\ValueObject\Uuid;

class OrderSeeder extends Seeder
{
    public const ID_FOR_FIRST_ORDER = '222abc0c-fc8e-4a1d-a4b0-d345cafacf95';
    public const ID_FOR_SECOND_ORDER = '222abc0c-fc8e-4a1d-a4b0-d345cafacf99';

    /**
     * Run the database seeds.
     *
     * @return void
     * @throws JsonException
     */
    public function run(): void
    {
        DB::table('order_tickets')->insert([
            'id' => self::ID_FOR_FIRST_ORDER,
            'guests' => json_encode([
                [
                    'value' => 'test',
                ]
            ], JSON_THROW_ON_ERROR),
            'user_id' => UserSeeder::ID_FOR_USER_UUID,
            'ticket_type_id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE,
            'promo_code' => PromoCodSeeder::NAME_FOR_SYSTO,
            'types_of_payment_id' => TypesOfPaymentSeeder::ID_FOR_YANDEX,
            'price' => TypeTicketsSeeder::PRICE_FOR_FIRST_WAVE,
            'discount' => PromoCodSeeder::DISCOUNT_FOR_SYSTO,
            'date' => '2022-12-16 18:24:00'
        ]);

        DB::table('order_tickets')->insert([
            'id' => self::ID_FOR_SECOND_ORDER,
            'guests' => json_encode([
                [
                    'value' => 'test',
                ]
            ], JSON_THROW_ON_ERROR),
            'user_id' => UserSeeder::ID_FOR_USER_UUID,
            'ticket_type_id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE,
            'types_of_payment_id' => TypesOfPaymentSeeder::ID_FOR_YANDEX,
            'price' => TypeTicketsSeeder::PRICE_FOR_FIRST_WAVE,
            'date' => '2022-12-16 18:24:00'
        ]);
    }
}
