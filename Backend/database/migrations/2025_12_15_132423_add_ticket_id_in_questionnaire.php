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
        Schema::table('questionnaire', function (Blueprint $table) {
            $table->string('phone')->after('telegram')->comment('Телефон');
            $table->uuid('ticket_id')->nullable()->default(null)->after('order_id')->comment('Id билета');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questionnaire', function (Blueprint $table) {
            $table->dropColumn('ticket_id');
            $table->dropColumn('phone');
        });
    }
};
