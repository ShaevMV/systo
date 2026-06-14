<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица дедупликации входящих межсервисных событий на стороне org.
 *
 * Гарантирует at-most-once бизнес-эффект: повторная доставка одного события qr → org
 * (сетевой ретрай, повторная публикация, redelivery после nack) не создаст дубль заказа.
 * Ключ — `idempotency_key` из конверта события (= "order.<qr_order_id>", см.
 * .claude/specs/qr-integration/CONTRACT_RFC_v0.md §3, §8).
 *
 * Зеркало таблицы `processed_messages` в Baza (там — для потока org → BAZA ticket.issued).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processed_messages', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key')->unique();
            $table->string('event_type')->nullable();
            $table->string('source')->nullable();
            $table->string('trace_id')->nullable();
            $table->timestamp('processed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_messages');
    }
};
