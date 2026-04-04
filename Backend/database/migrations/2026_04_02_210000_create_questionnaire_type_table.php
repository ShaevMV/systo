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
        Schema::create('questionnaire_type', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->comment('Название типа анкеты');
            $table->json('questions')->comment('Список вопросов анкеты');
            $table->boolean('active')->default(true)->comment('Активность типа анкеты');
            $table->integer('sort')->default(0)->comment('Сортировка');
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
        Schema::dropIfExists('questionnaire_type');
    }
};
