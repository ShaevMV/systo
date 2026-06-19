<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Проекция полей покупателя/фестиваля нового полного контракта qr в колонки qr_orders
 * (для списка/деталей/фильтров админки). Весь JSON по-прежнему хранится в payload as-is —
 * это лишь денормализация нужных полей. См. QrOrderDto::fromQrContract.
 *
 * Сумму нового контракта (payment.amount_total) НЕ дублируем — переиспользуем total_price.
 * ПДн расширенного контракта (child{}, payment.method_details.card_number и т.п.) в колонки
 * НЕ проецируем (остаются только в payload) — минимизация по 152-ФЗ/PCI.
 *
 * hasColumn-гарды — безопасный повторный прогон на непустой БД (как в TD-27 / 2026_06_18_140000).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_orders', static function (Blueprint $table): void {
            if (! Schema::hasColumn('qr_orders', 'buyer_fio')) {
                // ФИО покупателя (buyer.fio, fallback legacy user.name) — админка «кто заказал».
                $table->string('buyer_fio', 255)->nullable()->index()->after('phone');
            }
            if (! Schema::hasColumn('qr_orders', 'festival_title')) {
                // Название фестиваля (order_data.festival.title) — список/деталь без JOIN.
                $table->string('festival_title', 255)->nullable()->after('festival_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('qr_orders', static function (Blueprint $table): void {
            foreach (['buyer_fio', 'festival_title'] as $column) {
                if (Schema::hasColumn('qr_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
