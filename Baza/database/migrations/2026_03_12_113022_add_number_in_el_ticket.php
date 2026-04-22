<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('el_tickets', function (Blueprint $table) {
            $table->integer('number')->nullable(true)->default(null)->comment('номер живого билета');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('el_tickets', function (Blueprint $table) {
            $table->dropColumn('number');
        });
    }
};
