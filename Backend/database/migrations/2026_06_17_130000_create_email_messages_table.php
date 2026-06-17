<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Трекинг отправки писем (Ф2 системы писем): полный путь письма от постановки в очередь до
 * подтверждения отправки/прочтения. Текущий статус письма — здесь; таймлайн событий — в
 * domain_history (aggregate_type='email'). Содержит ПДн (recipient) → чтение только admin.
 *
 * Спека: .claude/specs/email-delivery-system.md (Часть 2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_messages', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('event', 40)->index();                 // EmailEvent (order_paid/order_cancel/...)
            $table->string('recipient')->index();                 // email получателя (ПДн, admin-only)
            $table->string('subject')->nullable();
            $table->string('template_slug')->nullable();          // фактический slug (информативно)
            $table->string('status', 20)->index();                // EmailStatus: queued/sending/sent/failed/...
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error')->nullable();                    // текст последней ошибки = «где застряло»
            $table->string('source', 20)->index();                // qr_pipeline / qr_intake / org_event
            $table->string('aggregate_type', 30)->nullable();     // qr_order / order_ticket / user
            $table->uuid('aggregate_id')->nullable();             // id заказа/пользователя (связь с экраном qr)
            $table->uuid('festival_id')->nullable();              // для фильтра
            $table->string('tracking_token', 64)->unique();       // токен пикселя прочтения (≠ id)
            $table->string('provider_message_id')->nullable();    // message-id от SMTP/провайдера (AF-6)
            $table->json('meta')->nullable();                     // доп. контекст (cast array)
            $table->longText('mailable')->nullable();             // base64(serialize(Mailable)) — для отправки/повтора
            $table->timestamp('sent_at')->nullable();             // момент передачи на SMTP
            $table->timestamp('opened_at')->nullable();           // момент первого открытия (пиксель, Ф3)
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['aggregate_type', 'aggregate_id']);
            $table->index(['festival_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_messages');
    }
};
