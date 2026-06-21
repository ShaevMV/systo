<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only журнал проходов на КПП (Ф5, PR-8) — гейт мульти-устройства.
 *
 * Телефоны накапливают намерения впуска офлайн (IndexedDB-очередь) и дренят их сюда при
 * связи. Сервер НЕ перезаписывает чужие записи — только добавляет (append-only) и
 * дедуплицирует по client_op_id (повторный дренаж того же намерения). «Первый впуск
 * побеждает»: если билет уже впущен (в журнале/таблице) — новая запись помечается
 * is_duplicate и НЕ накручивает счётчик смены. Счётчики пересчитываются из журнала.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('baza_entry_events')) {
            return;
        }

        Schema::create('baza_entry_events', function (Blueprint $table) {
            $table->id();
            $table->string('client_op_id', 64)->unique();  // идемпотентность дренажа
            $table->string('type', 32);                     // electron/spisok/live/auto/parking
            $table->integer('kilter')->nullable()->index();
            $table->char('ticket_uuid', 36)->nullable()->index();
            $table->string('device_id', 64)->nullable();    // какое устройство впустило
            $table->integer('change_id')->nullable();       // смена, в которую засчитан впуск
            $table->dateTime('entered_at')->nullable();     // время по часам устройства (для порядка)
            $table->boolean('is_duplicate')->default(false); // true = второй+ впуск (не засчитан)
            $table->char('festival_id', 36)->nullable()->index();
            $table->string('nonce', 64)->nullable();        // под HMAC-подпись (follow-up)
            $table->timestamps();
            $table->index(['type', 'kilter']);              // first-wins lookup
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baza_entry_events');
    }
};
