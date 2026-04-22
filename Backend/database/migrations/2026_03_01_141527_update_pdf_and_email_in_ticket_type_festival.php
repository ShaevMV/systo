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
        Schema::table('ticket_type_festival', function (Blueprint $table) {
            $table->string('email')->nullable()->default(null)->change();
            $table->string('pdf')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_type_festival', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }
};
