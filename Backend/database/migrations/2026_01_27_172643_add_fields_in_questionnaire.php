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
            $table->uuid('order_id')->nullable(true)->default(null)->change();
            $table->uuid('ticket_id')->nullable(true)->default(null)->change();
            $table->boolean('is_have_in_club')->default(false)->comment('Хочет участвовать в клубе')->after('musicStyles');
            $table->uuid('user_id')->nullable(true)->default(null)->comment('Uuid пользователя')->after('ticket_id');
            $table->string('festival_id')->nullable(false)->default('9d679bcf-b438-4ddb-ac04-023fa9bff4b8')->comment('Фестиваль')->after('user_id');
            $table->string('status')->nullable(false)->default('APPROVE')->comment('Статус по анкете')->after('festival_id');
            $table->string('email')->nullable(true)->default(null)->comment('email по которому будет создан пользователь')->after('phone');
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
            $table->dropColumn('festival_id');
            $table->dropColumn('is_have_in_club');
            $table->dropColumn('user_id');
        });
    }
};
