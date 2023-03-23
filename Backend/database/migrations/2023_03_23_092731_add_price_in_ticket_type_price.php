<?php

use Carbon\Carbon;
use Database\Seeders\TypeTicketsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tickets\Shared\Domain\ValueObject\Uuid;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('ticket_type_price')->insert([
            'id' => Uuid::random()->value(),
            'ticket_type_id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE,
            'price' => 4600,
            'before_date' => (new Carbon())->subDays(1),
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);
        DB::table('ticket_type_price')->insert([
            'id' => Uuid::random()->value(),
            'ticket_type_id' => TypeTicketsSeeder::ID_FOR_REGIONS,
            'price' => 4400,
            'before_date' => (new Carbon())->subDays(1),
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_type_price', function (Blueprint $table) {
            //
        });
    }
};
