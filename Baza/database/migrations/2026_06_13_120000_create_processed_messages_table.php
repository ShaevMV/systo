<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица дедупликации входящих межсервисных событий (прототип шины qr↔org↔BAZA).
 *
 * Гарантирует at-most-once бизнес-эффект: повторная доставка одного события
 * (сетевой ретрай, повторная публикация) не создаст дубль билета. Ключ —
 * `idempotency_key` из конверта события (см. .claude/specs/qr-integration/CONTRACT_RFC_v0.md §6).
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
