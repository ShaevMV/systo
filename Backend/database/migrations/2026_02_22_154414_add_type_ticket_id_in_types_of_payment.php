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
            $table->uuid('ticket_type_id')->nullable(true)->comment('Связь с типом билета');
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
            $table->dropColumn('ticket_type_id');
        });
    }
};
