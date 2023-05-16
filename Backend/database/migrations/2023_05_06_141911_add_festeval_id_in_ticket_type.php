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
            $table->uuid('festival_id')->default(env('UUID_FESTIVAL'))->after('groupLimit');
            $table->integer('sort')->default(0);
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
            $table->dropColumn('festival_id');
            $table->dropColumn('sort');
        });
    }
};
