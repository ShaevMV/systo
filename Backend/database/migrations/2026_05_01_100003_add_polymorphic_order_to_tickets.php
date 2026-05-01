<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Делает сущность tickets полиморфной.
 *
 * До этой миграции tickets.order_ticket_id → order_tickets (жёсткая связь).
 * После — tickets может принадлежать любому типу заказа:
 *   - guest_orders (order_type = 'guest')
 *   - friendly_orders (order_type = 'friendly')
 *   - live_orders (order_type = 'live')
 *   - и т.д.
 *
 * Обратная совместимость: order_ticket_id НЕ удаляется.
 * Старые билеты (order_ticket_id NOT NULL) продолжают работать.
 * Новые билеты используют order_type + order_id.
 *
 * Полиморфная связь в Eloquent:
 *   $ticket->orderable() → morphTo('orderable', 'order_type', 'order_id')
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Тип заказа: 'guest' | 'friendly' | 'live' | 'forest_card' | 'list' | 'parking'
            // NULL для старых записей из order_tickets
            $table->string('order_type')->nullable()->default(null)->after('order_ticket_id');

            // UUID заказа в соответствующей таблице (guest_orders, friendly_orders, etc.)
            // NULL для старых записей из order_tickets
            $table->uuid('order_id')->nullable()->default(null)->after('order_type');

            // Делаем order_ticket_id nullable для новых типов заказов
            $table->uuid('order_ticket_id')->nullable()->change();
        });

        // Индекс для быстрого поиска билетов по заказу
        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['order_type', 'order_id'], 'tickets_order_polymorphic_index');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_order_polymorphic_index');
            $table->dropColumn(['order_type', 'order_id']);
        });
    }
};
