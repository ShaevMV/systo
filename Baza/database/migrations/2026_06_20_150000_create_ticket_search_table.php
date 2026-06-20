<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Единый поисковый индекс билетов для ручного поиска на КПП (когда у гостя НЕТ QR).
 *
 * Наполняется из org→Baza ingest (Ф3) богатыми данными гостя (rich-контракт qr) + узким
 * fallback. Поиск Baza (`/search`) ищет по проиндексированным полям — ФИО/телефон/телега/
 * госномер/имя ребёнка и т.д. Локальная таблица → поиск работает ОФЛАЙН. Попадает на ноутбук
 * КПП через существующий синк (export/import), поэтому PK — автоинкрементный `id` (как у
 * остальных синкаемых таблиц), а uuid билета — отдельной колонкой.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ticket_search')) {
            return;
        }

        Schema::create('ticket_search', function (Blueprint $table) {
            $table->id();                                  // автоинкремент — для синка
            $table->char('ticket_uuid', 36)->unique();     // id билета в org (естественный ключ)
            $table->char('festival_id', 36)->nullable()->index();
            $table->string('type', 20);                    // electron|spisok|live|auto (DefineService)
            $table->integer('kilter')->nullable();         // номер билета — мост к впуску
            // Проекция искомых полей (лёгкие, индексируются — поиск НЕ по JSON):
            $table->string('fio')->nullable()->index();
            $table->string('phone', 64)->nullable()->index();
            $table->string('telegram', 64)->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('city')->nullable();
            $table->string('car_number', 64)->nullable()->index();
            $table->string('child_name')->nullable()->index();
            $table->string('parent_phone', 64)->nullable()->index();
            $table->string('external_order_no', 64)->nullable()->index();
            $table->string('type_ticket')->nullable();     // читаемый тип билета
            $table->json('payload')->nullable();           // весь полученный search/ticket as-is
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_search');
    }
};
