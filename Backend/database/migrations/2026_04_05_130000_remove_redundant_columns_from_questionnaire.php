<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Эта миграция удаляет старые колонки из questionnaire после переноса
     * данных в JSON. На чистой БД этих колонок может уже не быть.
     *
     * @return void
     */
    public function up()
    {
        $columnsToRemove = [
            'agy', 'howManyTimes', 'questionForSysto', 'is_have_in_club',
            'creationOfSisto', 'activeOfEvent', 'whereSysto', 'musicStyles',
            'name', 'vk',
        ];

        $existingColumns = [];
        foreach ($columnsToRemove as $col) {
            if (Schema::hasColumn('questionnaire', $col)) {
                $existingColumns[] = $col;
            }
        }

        if (!empty($existingColumns)) {
            Schema::table('questionnaire', function (Blueprint $table) use ($existingColumns) {
                $table->dropColumn($existingColumns);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questionnaire', function (Blueprint $table) {
            if (!Schema::hasColumn('questionnaire', 'agy')) {
                $table->integer('agy')->nullable()->default(null);
            }
            if (!Schema::hasColumn('questionnaire', 'howManyTimes')) {
                $table->string('howManyTimes')->nullable()->default(null);
            }
            if (!Schema::hasColumn('questionnaire', 'questionForSysto')) {
                $table->text('questionForSysto')->nullable()->default(null);
            }
            if (!Schema::hasColumn('questionnaire', 'is_have_in_club')) {
                $table->boolean('is_have_in_club')->nullable()->default(null);
            }
            if (!Schema::hasColumn('questionnaire', 'creationOfSisto')) {
                $table->text('creationOfSisto')->nullable()->default(null);
            }
            if (!Schema::hasColumn('questionnaire', 'activeOfEvent')) {
                $table->text('activeOfEvent')->nullable()->default(null);
            }
            if (!Schema::hasColumn('questionnaire', 'whereSysto')) {
                $table->text('whereSysto')->nullable()->default(null);
            }
            if (!Schema::hasColumn('questionnaire', 'musicStyles')) {
                $table->text('musicStyles')->nullable()->default(null);
            }
            if (!Schema::hasColumn('questionnaire', 'name')) {
                $table->string('name')->nullable()->default(null);
            }
            if (!Schema::hasColumn('questionnaire', 'vk')) {
                $table->string('vk')->nullable()->default(null);
            }
        });
    }
};
