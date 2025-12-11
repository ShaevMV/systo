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
            $table->uuid('order_id');
            $table->integer('agy')->comment('Возраст');
            $table->integer('howManyTimes')->comment('Сколько раз ты уже бывал на Систо');
            $table->text('questionForSysto')->comment('Ответь кратко и честно на простой вопрос Зачем ты едешь на Систо?');
            $table->string('telegram')->nullable()->default(null)->comment('Telegram');
            $table->string('vk')->nullable()->default(null)->comment('Вконтакте');
            $table->text('musicStyles')->nullable()->default(null)->comment('Стили музыки, которые предпочитаешь в лесу');
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
