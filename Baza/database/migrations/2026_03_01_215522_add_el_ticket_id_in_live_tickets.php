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
        Schema::table('live_tickets', function (Blueprint $table) {
            $table->uuid('el_ticket_id')->nullable(true)->comment('ссылка на электронный билет');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('live_tickets', function (Blueprint $table) {
            $table->dropColumn('el_ticket_id');
        });
    }
};
