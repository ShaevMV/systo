<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        // Шаг 1: Переносим данные из отдельных колонок в JSON data
        DB::statement("
            UPDATE questionnaire
            SET data = JSON_MERGE_PATCH(
                COALESCE(data, '{}'),
                JSON_OBJECT(
                    'agy', COALESCE(agy, 0),
                    'questionForSysto', COALESCE(questionForSysto, ''),
                    'phone', COALESCE(phone, ''),
                    'howManyTimes', COALESCE(howManyTimes, ''),
                    'is_have_in_club', COALESCE(is_have_in_club, 0),
                    'creationOfSisto', COALESCE(creationOfSisto, ''),
                    'activeOfEvent', COALESCE(activeOfEvent, ''),
                    'whereSysto', COALESCE(whereSysto, ''),
                    'musicStyles', COALESCE(musicStyles, ''),
                    'name', COALESCE(name, ''),
                    'vk', COALESCE(vk, '')
                )
            )
            WHERE data IS NULL OR JSON_LENGTH(COALESCE(data, '{}')) = 0
        ");

        // Шаг 2: Делаем все старые колонки nullable чтобы не было SQL ошибки
        Schema::table('questionnaire', function (Blueprint $table) {
            $table->integer('agy')->nullable()->default(null)->change();
            $table->text('questionForSysto')->nullable()->default(null)->change();
            $table->string('phone')->nullable()->default(null)->change();
            $table->string('howManyTimes')->nullable()->default(null)->change();
            $table->boolean('is_have_in_club')->nullable()->default(null)->change();
            $table->text('creationOfSisto')->nullable()->default(null)->change();
            $table->text('activeOfEvent')->nullable()->default(null)->change();
            $table->text('whereSysto')->nullable()->default(null)->change();
            $table->text('musicStyles')->nullable()->default(null)->change();
            $table->string('name')->nullable()->default(null)->change();
            $table->string('vk')->nullable()->default(null)->change();
        });

        // Шаг 3: Удаляем "Имя на билете" из гостевой анкеты
        $guestTypeId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $guestType = DB::table('questionnaire_type')->where('id', $guestTypeId)->first();
        if ($guestType) {
            $questions = json_decode($guestType->questions, true);
            $questions = array_filter($questions, fn($q) => $q['name'] !== 'name');
            DB::table('questionnaire_type')
                ->where('id', $guestTypeId)
                ->update(['questions' => json_encode(array_values($questions))]);
        }

        // Шаг 4: Удаляем "Имя на билете" из анкеты нового пользователя
        $newUserId = 'b2c3d4e5-f6a7-8901-bcde-f23456789012';
        $newUserType = DB::table('questionnaire_type')->where('id', $newUserId)->first();
        if ($newUserType) {
            $questions = json_decode($newUserType->questions, true);
            $questions = array_filter($questions, fn($q) => $q['name'] !== 'name');
            DB::table('questionnaire_type')
                ->where('id', $newUserId)
                ->update(['questions' => json_encode(array_values($questions))]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Восстанавливаем NOT NULL
        Schema::table('questionnaire', function (Blueprint $table) {
            $table->integer('agy')->nullable(false)->default(0)->change();
            $table->text('questionForSysto')->nullable(false)->default('')->change();
            $table->string('phone')->nullable(false)->default('')->change();
        });

        // Восстанавливаем "Имя на билете" в гостевой анкете
        $guestTypeId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $guestType = DB::table('questionnaire_type')->where('id', $guestTypeId)->first();
        if ($guestType) {
            $questions = json_decode($guestType->questions, true);
            $questions[] = [
                'title' => 'Имя на билете',
                'name' => 'name',
                'type' => 'string',
                'validate' => null,
                'required' => false,
            ];
            DB::table('questionnaire_type')
                ->where('id', $guestTypeId)
                ->update(['questions' => json_encode($questions)]);
        }

        $newUserId = 'b2c3d4e5-f6a7-8901-bcde-f23456789012';
        $newUserType = DB::table('questionnaire_type')->where('id', $newUserId)->first();
        if ($newUserType) {
            $questions = json_decode($newUserType->questions, true);
            $questions[] = [
                'title' => 'Имя на билете',
                'name' => 'name',
                'type' => 'string',
                'validate' => null,
                'required' => false,
            ];
            DB::table('questionnaire_type')
                ->where('id', $newUserId)
                ->update(['questions' => json_encode($questions)]);
        }
    }
};
