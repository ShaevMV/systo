<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица входящих заказов от витрины qr.spaceofjoy.ru (anti-corruption staging-таблица).
 *
 * Хранит расширенный JSON-контракт заказа as-is в `payload` (источник истины) + плоские
 * проекционные колонки для фильтрации в админке (email/status/festival_id/type_order/city).
 * PK = id заказа из qr (он же РАВЕН id заказа в org — маппинг не нужен).
 *
 * Решение tech-lead (2026-06-14): отдельная таблица в БД systo, НЕ переиспользуем order_tickets;
 * паттерн «JSON + индексируемые проекции» как в questionnaire.data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_orders', static function (Blueprint $table): void {
            $table->uuid('id')->primary(); // == id заказа в qr (== org)

            // Проекция полей контракта для фильтрации (денормализация из payload при приёме).
            $table->string('email')->index();
            $table->string('status')->index();
            $table->uuid('festival_id')->nullable()->index();
            $table->string('type_order')->nullable()->index();
            $table->string('city')->nullable()->index();
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('total_price')->default(0); // целые рубли (как Money VO)

            // Сырой контракт qr целиком — источник истины.
            $table->json('payload');

            // Когда заказ переведён в выдачу (отработал API №2 смены статуса).
            $table->timestamp('issued_at')->nullable();

            $table->timestamps();

            // Составные индексы под типовые фильтры админки.
            $table->index(['festival_id', 'status']);
            $table->index(['festival_id', 'type_order']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_orders');
    }
};
