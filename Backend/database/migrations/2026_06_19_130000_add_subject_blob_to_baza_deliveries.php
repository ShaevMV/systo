<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Хранилище сериализованного субъекта доставки (TicketResponse) для целей el_tickets/spisok_tickets.
 *
 * Зачем: `getTicket()` пересобирает билет из `order_tickets`, но у qr-билета заказ лежит в `qr_orders`
 * → JOIN пустой → `status` и др. поля null → `TicketResponse` падает. Поэтому диспетчер сериализует
 * уже готовый `TicketResponse` (как `email_messages.mailable`), а job берёт его отсюда (с fallback на
 * `getTicket` для классических order_tickets-билетов). Поддерживает resend (субъект сохранён).
 *
 * hasColumn-гард — для безопасного повторного прогона (как в TD-27).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('baza_deliveries', static function (Blueprint $table): void {
            if (! Schema::hasColumn('baza_deliveries', 'subject_blob')) {
                // base64(serialize(TicketResponse)) — для записи билета в Baza/повтора. Тяжёлый, не в проекции списка.
                $table->longText('subject_blob')->nullable()->after('source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('baza_deliveries', static function (Blueprint $table): void {
            if (Schema::hasColumn('baza_deliveries', 'subject_blob')) {
                $table->dropColumn('subject_blob');
            }
        });
    }
};
