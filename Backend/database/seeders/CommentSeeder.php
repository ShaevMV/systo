<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use JsonException;
use Tickets\Shared\Domain\ValueObject\Uuid;

class CommentSeeder extends Seeder
{
    public const ID_FOR_FIRST_COMMENT = 'bdec45aa-06e6-45d7-8b6b-f12f0b289d5e';
    public const ID_FOR_SECOND_COMMENT = 'bdec45aa-06e6-45d7-8b6b-f12f0b289d5f';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        if(env('APP_DEBUG')) {
            DB::table('comment')->insert([
                'id' => self::ID_FOR_FIRST_COMMENT,
                'user_id' => UserSeeder::ID_FOR_USER_UUID,
                'order_tickets_id' => OrderSeeder::ID_FOR_FIRST_ORDER,
                'comment' => 'Test request',
                'created_at' => new Carbon(),
                'updated_at' => new Carbon(),
            ]);

            DB::table('comment')->insert([
                'id' => self::ID_FOR_SECOND_COMMENT,
                'user_id' => UserSeeder::ID_FOR_ADMIN_UUID,
                'order_tickets_id' => OrderSeeder::ID_FOR_FIRST_ORDER,
                'comment' => 'Test response',
                'created_at' => new Carbon(),
                'updated_at' => new Carbon(),
            ]);
        }
    }
}
