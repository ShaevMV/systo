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
            $table->uuid('questionnaire_type_id')->nullable()->default(null)->after('is_live_ticket')->comment('ID типа анкеты');
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
            $table->dropColumn('questionnaire_type_id');
        });
    }
};
