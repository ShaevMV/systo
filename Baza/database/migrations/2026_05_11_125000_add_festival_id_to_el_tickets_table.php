<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Синхронизация миграций со схемой прода.
 *
 * Колонка `festival_id` в `el_tickets` есть на проде/локалке (добавлена руками),
 * но миграции для неё в репо не было. Backfill-миграция
 * `2026_05_11_130000_backfill_festival_id_in_el_tickets` падает на чистой БД,
 * потому что update обращается к несуществующей колонке. Эта миграция
 * добавляет колонку ровно перед backfill (по timestamp 125000 < 130000).
 *
 * Schema::hasColumn — чтобы на проде/локалке миграция не пыталась добавить
 * существующую колонку повторно.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('el_tickets', 'festival_id')) {
            Schema::table('el_tickets', function (Blueprint $table) {
                $table->uuid('festival_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('el_tickets', 'festival_id')) {
            Schema::table('el_tickets', function (Blueprint $table) {
                $table->dropColumn('festival_id');
            });
        }
    }
};
