<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        // На части окружений (в т.ч. прод) у tickets есть только ИНДЕКС
        // tickets_order_ticket_id_foreign, но самого FK-ограничения нет — безусловный
        // dropForeign падал бы (SQLSTATE[42000] 1091 «Can't DROP ...; check that key exists»).
        // Снимаем FK только если он реально существует.
        if ($this->foreignKeyExists('tickets', 'order_ticket_id')) {
            Schema::table('tickets', static function (Blueprint $table): void {
                $table->dropForeign(['order_ticket_id']);
            });
        }
    }

    /**
     * Есть ли на колонке внешний ключ (именно FK-ограничение, а не просто индекс).
     */
    private function foreignKeyExists(string $table, string $column): bool
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();
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
