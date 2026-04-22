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
            $table->json('data')->nullable()->default(null)->after('status')->comment('JSON поле для хранения данных анкеты');
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
            $table->dropColumn('data');
        });
    }
};
