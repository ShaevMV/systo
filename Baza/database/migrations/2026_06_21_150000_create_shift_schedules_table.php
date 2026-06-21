<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Плановое расписание смен КПП (PR-A).
 *
 * Заранее составленная сетка смен (дата/время/точка КПП/начальник/состав) — основа
 * для авто-открытия смены (changes) и личного расписания сотрудника. Сама смена
 * (changes) остаётся фактом «кто реально работал»; shift_schedules — это план.
 *
 * Без FK — целостность на уровне приложения (паттерн Baza, как change_user).
 * Schema::hasTable() — на случай ручного создания таблицы на проде
 * (паттерн 2026_05_29_180000 / 2026_06_19_170000).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shift_schedules')) {
            return;
        }

        Schema::create('shift_schedules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('festival_id');
            $table->string('kpp_point')->nullable(); // точка КПП (несколько входов на фестивале)
            $table->date('shift_date');
            $table->dateTime('planned_start');
            $table->dateTime('planned_end')->nullable();
            $table->string('name')->nullable(); // человекочитаемое имя плана (напр. «Утро, главный КПП»)
            $table->string('status')->default('planned'); // planned | cancelled (далее — opened авто-открытием)
            $table->unsignedBigInteger('chief_id')->nullable(); // user_id начальника смены
            $table->timestamps();

            $table->index(['festival_id', 'shift_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_schedules');
    }
};
