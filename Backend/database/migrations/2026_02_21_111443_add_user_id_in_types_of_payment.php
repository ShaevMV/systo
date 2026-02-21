<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('types_of_payment', function (Blueprint $table) {
            $table->uuid('user_external_id')
                ->nullable(true)
                ->default(null)
                ->comment('Связь с продавцом или реализатором');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('types_of_payment', function (Blueprint $table) {
            $table->dropColumn('user_external_id');
        });
    }
};
