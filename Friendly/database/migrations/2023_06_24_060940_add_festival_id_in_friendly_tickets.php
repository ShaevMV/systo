<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFestivalIdInFriendlyTickets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('friendly_tickets', function (Blueprint $table) {
            $table->uuid('festival_id')->default('9d679bcf-b438-4ddb-ac04-023fa9bff4b2');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('friendly_tickets', function (Blueprint $table) {
            $table->dropColumn('festival_id');
        });
    }
}
