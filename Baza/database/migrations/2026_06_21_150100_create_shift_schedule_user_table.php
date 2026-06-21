<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Состав планового расписания смены с ролями (PR-A).
 *
 * Зеркало change_user, но для ПЛАНА (shift_schedules), а не факта (changes).
 * Строка на каждого запланированного участника + его роль в смене.
 *
 * Без FK — целостность на уровне приложения (паттерн Baza). UNIQUE(schedule_id, user_id)
 * — один человек в плановой смене ровно один раз. Schema::hasTable() — паттерн Baza.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shift_schedule_user')) {
            return;
        }

        Schema::create('shift_schedule_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('schedule_id')->index();
            $table->unsignedBigInteger('user_id');
            $table->string('role', 40); // код из Baza\Shared\Domain\ValueObject\ShiftRole
            $table->timestamps();

            $table->unique(['schedule_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_schedule_user');
    }
};
