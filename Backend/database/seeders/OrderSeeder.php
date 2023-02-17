<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use JsonException;
use Tickets\Shared\Domain\ValueObject\Uuid;

class OrderSeeder extends Seeder
{
    public const ID_FOR_FIRST_ORDER = '222abc0c-fc8e-4a1d-a4b0-d345cafacf95';
    public const ID_FOR_SECOND_ORDER = '222abc0c-fc8e-4a1d-a4b0-d345cafacf99';
    public const ID_FOR_FIRST_TICKET = '56f04400-02ab-4cbe-bfd4-4f7dda23d675';
    public const ID_FOR_SECOND_TICKET = '56f04400-02ab-4cbe-bfd4-4f7dda23d676';

    /**
     * Run the database seeds.
     *
     * @return void
     * @throws JsonException
     */
    public function run(): void
    {
        if(env('APP_DEBUG')) {
            DB::table('order_tickets')->insert([
                'id' => self::ID_FOR_FIRST_ORDER,
                'guests' => json_encode([
                    [
                        'value' => 'test',
                        'id' => self::ID_FOR_FIRST_TICKET,
                    ]
                ], JSON_THROW_ON_ERROR),
                'festival_id' => FestivalSeeder::ID_FOR_2023_FESTIVAL,
                'id_buy' => '2312',
                'phone' => '+9999999999',
                'user_id' => UserSeeder::ID_FOR_USER_UUID,
                'ticket_type_id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE,
                'promo_code' => PromoCodSeeder::NAME_FOR_SYSTO,
                'types_of_payment_id' => TypesOfPaymentSeeder::ID_FOR_YANDEX,
                'price' => TypeTicketsSeeder::PRICE_FOR_FIRST_WAVE,
                'discount' => PromoCodSeeder::DISCOUNT_FOR_SYSTO,
                'date' => '2022-12-16 18:24:00',
                'created_at' => new Carbon(),
                'updated_at' => new Carbon(),
            ]);

            DB::table('order_tickets')->insert([
                'id' => self::ID_FOR_SECOND_ORDER,
                'guests' => json_encode([
                    [
                        'value' => 'test',
                        'id' => self::ID_FOR_SECOND_TICKET,
                    ]
                ], JSON_THROW_ON_ERROR),
                'festival_id' => FestivalSeeder::ID_FOR_2023_FESTIVAL,
                'id_buy' => '2312',
                'phone' => '+9999999999',
                'user_id' => UserSeeder::ID_FOR_USER_UUID,
                'ticket_type_id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE,
                'types_of_payment_id' => TypesOfPaymentSeeder::ID_FOR_YANDEX,
                'price' => TypeTicketsSeeder::PRICE_FOR_FIRST_WAVE,
                'date' => '2022-12-16 18:24:00',
                'created_at' => new Carbon(),
                'updated_at' => new Carbon(),
            ]);
        }

    }
}
