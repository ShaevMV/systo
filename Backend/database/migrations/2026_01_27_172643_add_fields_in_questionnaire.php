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
            $table->string('email')->nullable(true)->default(null)->comment('email по которому будет создан пользователь')->after('musicStyles');
            $table->string('status')->nullable(false)->default('new')->comment('Статус по анкете')->after('musicStyles');
            $table->boolean('is_have_in_club')->default(false)->comment('Хочет участвовать в клубе')->after('musicStyles');
            $table->uuid('user_id')->nullable(true)->default(null)->comment('Uuid пользователя')->after('musicStyles');
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
            $table->dropColumn('email');
            $table->dropColumn('status');
            $table->dropColumn('is_have_in_club');
            $table->dropColumn('user_id');
        });
    }
};
