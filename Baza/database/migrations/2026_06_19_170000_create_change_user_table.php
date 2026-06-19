<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Состав смены с ролями (Ф2).
 *
 * Двойная запись параллельно changes.user_id (JSON): на переходный период
 * читающие экраны (getChangeId/getAllReport) используют старый JSON, а change_user
 * наполняется для будущего RBAC/UI (PR-4..6). Вход НЕ меняется.
 *
 * Без FK — целостность на уровне приложения (паттерн Baza). Schema::hasTable()
 * — на случай ручного создания таблицы на проде (паттерн 2026_05_29_180000).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('change_user')) {
            return;
        }

        Schema::create('change_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('change_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('role', 40); // код из Baza\Shared\Domain\ValueObject\ShiftRole
            $table->timestamps();

            // один человек в смене ровно один раз
            $table->unique(['change_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_user');
    }
};
