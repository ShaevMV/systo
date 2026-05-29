<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Синхронизация миграций со схемой прода.
 *
 * На проде и локально в таблице `changes` уже есть колонки `festival_id`
 * и `count_auto_tickets` — они добавлены руками, но миграции для них в
 * репозитории не было. На чистой БД (CI / новые dev-окружения) их нет,
 * и репозиторий InMemoryMySqlChangesRepository::getAllReport() падает.
 *
 * Миграция использует Schema::hasColumn() — на проде/локалке колонки
 * уже есть, ничего не сломает; на чистой БД добавит недостающее.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('changes', function (Blueprint $table) {
            if (! Schema::hasColumn('changes', 'festival_id')) {
                $table->string('festival_id')->nullable();
            }

            if (! Schema::hasColumn('changes', 'count_auto_tickets')) {
                $table->integer('count_auto_tickets')->nullable()->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('changes', function (Blueprint $table) {
            if (Schema::hasColumn('changes', 'festival_id')) {
                $table->dropColumn('festival_id');
            }

            if (Schema::hasColumn('changes', 'count_auto_tickets')) {
                $table->dropColumn('count_auto_tickets');
            }
        });
    }
};
