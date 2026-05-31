<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Волны цен для опций (v2.6.0).
 *
 * Структура полностью аналогична `ticket_type_price`. Для каждой опции
 * можно завести несколько записей с разными `before_date` — актуальной
 * считается ближайшая, где `before_date >= CURDATE()`.
 *
 * Цена опции хранится в INT (рубли целиком, без копеек) — фестиваль
 * не оперирует копейками. Это намеренно отличается от `ticket_type_price`,
 * где исторически float — менять там тип чревато.
 *
 * См. `.claude/specs/ticket-options.md`.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('option_price', static function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('option_id');
            $table->foreign('option_id')->references('id')->on('options')->cascadeOnDelete();

            $table->integer('price');
            $table->dateTime('before_date');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_price');
    }
};
