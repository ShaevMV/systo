<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Outbox вебхука «билет прошёл» Baza→org (Ф4).
 *
 * При впуске билета на КПП Baza пишет сюда запись (append-only буфер), а фоновый дренаж
 * (`baza:drain-entry-outbox`) при наличии сети шлёт её на org. Очередь КПП не ждёт org —
 * на офлайн-ноутбуке запись копится локально и доезжает, когда появится связь.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('baza_entry_outbox')) {
            return;
        }

        Schema::create('baza_entry_outbox', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('target', 20);              // el_tickets/spisok_tickets/live_tickets/auto
            $table->char('ticket_uuid', 36)->nullable(); // идентификатор билета в org
            $table->integer('kilter')->nullable();      // номер билета в Baza (для справки)
            $table->integer('change_id')->nullable();   // смена, в которую впущен
            $table->dateTime('entered_at');             // время впуска
            $table->string('wristband_qr')->nullable(); // QR браслета (Ф6, forward-compat)
            $table->string('status', 20)->default('pending'); // pending/sending/sent/failed
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['target', 'ticket_uuid']);  // идемпотентность: один билет — одно событие входа
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baza_entry_outbox');
    }
};
