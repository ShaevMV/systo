<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Глобальная роль-дефолт пользователя (Ф2).
 *
 * Используется в мягком маппинге ShiftRole::fromUser (явная role перекрывает
 * is_admin-маппинг) для производной роли участника смены. is_admin НЕ удаляем —
 * вход не ломаем. nullable: пока роль не задана, маппинг идёт по is_admin.
 *
 * Schema::hasColumn() guard — на проде Baza колонку могли добавить руками
 * (паттерн 2026_05_29_180000), иначе миграция упадёт Duplicate column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 40)->nullable()->after('is_admin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
