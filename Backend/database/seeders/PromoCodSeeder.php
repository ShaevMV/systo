<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Shared\Domain\ValueObject\Uuid;

class PromoCodSeeder extends Seeder
{
    public const ID_FOR_SYSTO = '2ecd9108-12c0-4ef1-9095-917442673a3c';
    public const ID_FOR_ILLUNIMISCATA = '2ecd9108-12c0-4ef1-9095-917442673a90';
    public const NAME_FOR_SYSTO = 'SYSTO20';
    public const DISCOUNT_FOR_SYSTO = 600;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('promo_code')->insert([
            'id' => self::ID_FOR_SYSTO,
            'name' => self::NAME_FOR_SYSTO,
            'discount' => self::DISCOUNT_FOR_SYSTO,
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
            'active' => true
        ]);

        DB::table('promo_code')->insert([
            'id' => self::ID_FOR_ILLUNIMISCATA,
            'name' => 'illunimiscata',
            'discount' => 500,
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
            'is_percent' => false,
            'limit' => 20,
        ]);
    }
}
