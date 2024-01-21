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
        Schema::table('ticket_type', function (Blueprint $table) {
            if (Schema::hasColumn('ticket_type', 'festival_id')) {
                $table->dropColumn('festival_id');
            }

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_type', function (Blueprint $table) {
            if (!Schema::hasColumn('ticket_type', 'festival_id')) {
                $table->uuid('festival_id');
            }

        });
    }
};
