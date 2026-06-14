<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Снимает внешний ключ tickets.order_ticket_id → order_tickets(id).
 *
 * Зачем: билеты заказов из витрины qr хранятся в той же таблице tickets, но их «заказ» живёт
 * в qr_orders (id заказа qr == order_ticket_id билета), а не в order_tickets. FK блокировал
 * автономную выдачу (решение B). После снятия FK поле order_ticket_id — обычный uuid,
 * указывающий на order_tickets ЛИБО на qr_orders; валидность гарантирует код приложения.
 *
 * Индекс на order_ticket_id (создавался под FK) сохраняется — JOIN'ы в getTicket не страдают.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', static function (Blueprint $table): void {
            $table->dropForeign(['order_ticket_id']);
        });
    }

    public function down(): void
    {
        // Откат вернёт FK. Сработает только если все order_ticket_id ссылаются на order_tickets
        // (т.е. до появления qr-билетов).
        Schema::table('tickets', static function (Blueprint $table): void {
            $table->foreign('order_ticket_id')->references('id')->on('order_tickets');
        });
    }
};
