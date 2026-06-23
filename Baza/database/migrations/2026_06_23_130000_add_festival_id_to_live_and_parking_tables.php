<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Дыра TD-48 (PR-6): live_tickets и parking_tickets не имели festival_id вовсе — на
 * мультифестивальном входе живой/парковочный билет чужого феста проходил мимо изоляции.
 *
 * Добавляем festival_id (nullable) + backfill существующих строк дефолтным фестивалём
 * (текущий пул номеров = текущее событие). Дальше: live получает реальный festival из
 * связанного el при ingest-линковке; create() пула проставляет дефолт. Фильтр в репо —
 * lenient (festival ИЛИ NULL), чтобы непомеченные номера не терялись из впуска.
 *
 * Schema::hasColumn() — на случай ручного добавления колонки на проде (паттерн el_tickets).
 */
return new class extends Migration
{
    public function up(): void
    {
        $default = (string) config('baza.default_festival_id');

        if (! Schema::hasColumn('live_tickets', 'festival_id')) {
            Schema::table('live_tickets', function (Blueprint $table) {
                $table->string('festival_id')->nullable()->index();
            });
        }
        if (! Schema::hasColumn('parking_tickets', 'festival_id')) {
            Schema::table('parking_tickets', function (Blueprint $table) {
                $table->string('festival_id')->nullable()->index();
            });
        }

        if ($default !== '') {
            DB::table('live_tickets')->whereNull('festival_id')->update(['festival_id' => $default]);
            DB::table('parking_tickets')->whereNull('festival_id')->update(['festival_id' => $default]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('live_tickets', 'festival_id')) {
            Schema::table('live_tickets', function (Blueprint $table) {
                $table->dropColumn('festival_id');
            });
        }
        if (Schema::hasColumn('parking_tickets', 'festival_id')) {
            Schema::table('parking_tickets', function (Blueprint $table) {
                $table->dropColumn('festival_id');
            });
        }
    }
};
