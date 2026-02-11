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
        Schema::create('questionnaire', function (Blueprint $table) {
            $table->id();
            $table->integer('external_id')->comment('внешний id')->nullable(false);
            $table->string('type')->comment('Тип билета')->nullable(false);
            $table->integer('agy')->comment('Возраст');
            $table->integer('howManyTimes')->comment('Сколько раз ты уже бывал на Систо');
            $table->text('questionForSysto')->comment('Ответь кратко и честно на простой вопрос Зачем ты едешь на Систо?');
            $table->string('telegram')->nullable()->default(null)->comment('Telegram');
            $table->string('vk')->nullable()->default(null)->comment('Вконтакте');
            $table->text('musicStyles')->nullable()->default(null)->comment('Стили музыки, которые предпочитаешь в лесу');
            $table->boolean('is_have_in_club')->default(false)->comment('Хочет участвовать в клубе');
            $table->string('festival_id')->nullable(false)->default('9d679bcf-b438-4ddb-ac04-023fa9bff4b8')->comment('Фестиваль');
            $table->string('status')->nullable(false)->default('APPROVE')->comment('Статус по анкете');
            $table->string('email')->nullable(true)->default(null)->comment('email по которому будет создан пользователь');
            $table->text('whereSysto')
                ->nullable(true)
                ->default(null)
                ->comment('Откуда ты узнал о Систо');
            $table->text('creationOfSisto')
                ->nullable(true)
                ->default(null)
                ->comment('Считаете ли вы себя участвующим в сотворении Систо');

            $table->text('activeOfEvent')
                ->nullable(true)
                ->default(null)
                ->comment('Готовы принимать более активное или творческое участие в создании события');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('questionnaire');
    }
};
