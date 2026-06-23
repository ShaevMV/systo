<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Реестр фестивалей на Vhod (Baza) — фундамент сервиса фестивалей (TD-48, PR-1).
 *
 * До этого «фестиваль» в Baza был зашитой константой `9d679bcf-…` (см. BAZA.md §9).
 * Теперь это РЕЕСТР: read-реплика каталога фестивалей из org (мастер — org) + один
 * локальный флаг `active_for_kpp`, которым реально владеет КПП (попадает ли фестиваль
 * в выбор при открытии смены / в офлайн-снимок). `id` == festival_id заказа/билета в org.
 *
 * Без FK — целостность на уровне приложения (паттерн Baza, как change_user/shift_schedules).
 * Schema::hasTable() — на случай ручного создания таблицы на проде (паттерн 2026_05_29_180000).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('festivals')) {
            return;
        }

        Schema::create('festivals', function (Blueprint $table) {
            $table->uuid('id')->primary();          // == festival_id заказа/билета в org
            $table->string('name');
            $table->unsignedSmallInteger('year')->nullable();
            $table->boolean('active')->default(true);          // зеркало org-каталога (информативно)
            $table->boolean('active_for_kpp')->default(true);  // локальный тумблер: доступен для выбора смены
            $table->timestamps();

            $table->index('active_for_kpp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('festivals');
    }
};
