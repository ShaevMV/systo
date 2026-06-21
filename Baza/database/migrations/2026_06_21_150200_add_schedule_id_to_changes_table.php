<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Связь факта смены (changes) с её планом (shift_schedules) — PR-A.
 *
 * changes.schedule_id заполняется при авто-открытии смены из плана (отдельный PR):
 * по нему видно, по какому плану открыта реальная смена. Nullable — смену по-прежнему
 * можно открыть вручную без плана (текущий путь не меняется).
 *
 * Без FK — целостность на уровне приложения (паттерн Baza). Schema::hasColumn-гард —
 * на проде колонку могли добавить руками (паттерн 2026_05_29_180000).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('changes', 'schedule_id')) {
            Schema::table('changes', function (Blueprint $table) {
                $table->unsignedBigInteger('schedule_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('changes', 'schedule_id')) {
            Schema::table('changes', function (Blueprint $table) {
                $table->dropColumn('schedule_id');
            });
        }
    }
};
