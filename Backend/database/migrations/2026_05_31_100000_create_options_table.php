<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица опций к билетам (v2.6.0).
 *
 * Опция — это дополнительное условие, прикрепляемое к билету
 * со своей стоимостью. Пример: «Саженец» (+500₽) к билету
 * «Цифровой оргвзнос соорганизатора».
 *
 * Цена опции хранится отдельно в таблице `option_price` (волны цен по
 * аналогии с `ticket_type_price`). Описание опции — на уровне привязки
 * к конкретному типу билета (pivot `option_ticket_type.description`),
 * поскольку для разных типов билетов одна и та же опция может иметь
 * разный смысл.
 *
 * Привязка к типам билетов — through `option_ticket_type` (many-to-many).
 * Снапшот цены/имени на момент покупки — в `order_ticket_options`
 * (создаётся в `feat/v2.6.0-order-domain-rewrite`).
 *
 * См. `.claude/specs/ticket-options.md`.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('options', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->boolean('active')->default(true);

            $table->uuid('festival_id');
            $table->foreign('festival_id')->references('id')->on('festivals');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};
