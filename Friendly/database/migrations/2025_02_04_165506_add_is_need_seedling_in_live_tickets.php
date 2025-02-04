<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsNeedSeedlingInLiveTickets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('live_tickets', function (Blueprint $table) {
            $table->boolean('is_need_seedling')->default(false);
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
            $table->dropColumn('is_need_seedling');
        });
    }
}
