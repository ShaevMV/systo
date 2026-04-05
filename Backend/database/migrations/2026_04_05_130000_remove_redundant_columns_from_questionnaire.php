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
        // Удаляем старые колонки — все данные уже в JSON колонке data,
        // а nullable-колонки уже не вызывают ошибок
        Schema::table('questionnaire', function (Blueprint $table) {
            $table->dropColumn([
                'agy',
                'howManyTimes',
                'questionForSysto',
                'is_have_in_club',
                'creationOfSisto',
                'activeOfEvent',
                'whereSysto',
                'musicStyles',
                'name',
                'vk',
            ]);
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
            $table->integer('agy')->nullable()->default(null);
            $table->string('howManyTimes')->nullable()->default(null);
            $table->text('questionForSysto')->nullable()->default(null);
            $table->boolean('is_have_in_club')->nullable()->default(null);
            $table->text('creationOfSisto')->nullable()->default(null);
            $table->text('activeOfEvent')->nullable()->default(null);
            $table->text('whereSysto')->nullable()->default(null);
            $table->text('musicStyles')->nullable()->default(null);
            $table->string('name')->nullable()->default(null);
            $table->string('vk')->nullable()->default(null);
        });
    }
};
