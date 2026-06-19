<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Трекинг доставки билетов в Baza («система входа»): полный путь записи билета в Baza от постановки
 * в очередь до подтверждения/ошибки. Текущий статус доставки — здесь; история ВСЕХ попыток — в
 * domain_history (aggregate_type='baza_delivery'). Содержит ПДн (name/email) → чтение только admin.
 *
 * Одна строка на (ticket_id, target) — UNIQUE-ключ идемпотентности (повторный sync билета не плодит
 * строки трекинга). hasTable/hasColumn-гарды — для безопасного повторного прогона (как в TD-27).
 *
 * Спека: .claude/specs/baza-delivery-async-prompt.md (§3.2).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('baza_deliveries')) {
            return;
        }

        Schema::create('baza_deliveries', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');                          // билет нашей системы (== el_tickets.uuid; для auto — auto.id)
            $table->uuid('order_id')->nullable()->index();      // заказ (связь с экраном qr / «весь путь»)
            $table->string('target', 20)->index();              // el_tickets / spisok_tickets / live_tickets / auto
            $table->string('status', 20)->index();              // BazaDeliveryStatus: queued/sending/delivered/failed
            $table->unsignedTinyInteger('attempts')->default(0); // число попыток (кап = 10, см. §6.4)
            $table->text('error')->nullable();                  // текст последней ошибки = «где застряло»
            $table->string('name')->nullable();                 // ФИО для отображения (ПДн, admin-only)
            $table->string('email')->nullable();                // email для отображения (ПДн, admin-only)
            $table->integer('number')->nullable();              // номер живого билета (kilter) / null
            $table->uuid('festival_id')->nullable();            // для фильтра/дашборда
            $table->string('source', 20)->index();              // qr_pipeline / org_event
            $table->timestamp('delivered_at')->nullable();      // момент успешной записи в Baza
            $table->timestamps();

            $table->unique(['ticket_id', 'target']);            // идемпотентность: одна доставка на (билет, цель)
            $table->index(['status', 'created_at']);
            $table->index(['festival_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baza_deliveries');
    }
};
