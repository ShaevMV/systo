<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('promo_code')->insert([
            'id' => '2ecd9108-12c0-4ef1-9095-917442673a88',
            'name' => 'perenos',
            'discount' => 100,
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
            'is_percent' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
