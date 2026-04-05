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
        // Добавляем поле code
        Schema::table('questionnaire_type', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('name')->comment('Уникальный код типа анкеты (slug)');
        });

        // Заполняем коды для существующих типов
        // Гостевая анкета
        DB::table('questionnaire_type')
            ->where('name', 'Гостевая анкета')
            ->update(['code' => 'guest']);

        // Анкета нового пользователя (если уже создана миграцией 2026_04_04)
        DB::table('questionnaire_type')
            ->where('name', 'Анкета нового пользователя')
            ->update(['code' => 'new_user']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questionnaire_type', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
