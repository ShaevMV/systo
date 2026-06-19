<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Матрица прав «роль × действие» (Ф2), редактируемая из UI.
 *
 * Наличие строки (role, action) = право есть; отсутствие = запрет. Роль
 * administrator — суперроль (короткозамкнута в коде, в таблицу НЕ пишется).
 * Готовит дизайн-контракт под незаконченную org-матрицу (.claude/specs/admin-rbac.md),
 * но физически отдельная таблица в схеме baza (без cross-schema).
 *
 * Schema::hasTable() guard — паттерн Baza (на проде таблицу могли создать руками).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('baza_role_permissions')) {
            return;
        }

        Schema::create('baza_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 40);   // код ShiftRole
            $table->string('action', 60);  // код ShiftPermission
            $table->timestamps();

            $table->unique(['role', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baza_role_permissions');
    }
};
