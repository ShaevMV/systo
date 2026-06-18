<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Проекция дополнительных полей расширенного контракта qr в колонки qr_orders
 * (для списка/фильтров/отчётности админки). Весь JSON по-прежнему хранится в payload as-is;
 * это — денормализация нужных полей. См. QrOrderDto::fromQrContract.
 *
 * hasColumn-гарды — для безопасного повторного прогона на непустой БД (как в TD-27).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_orders', static function (Blueprint $table): void {
            if (! Schema::hasColumn('qr_orders', 'external_order_no')) {
                // Человекочитаемый номер заказа qr (external_order_no) — для поиска/фильтра.
                $table->string('external_order_no', 64)->nullable()->index()->after('phone');
            }
            if (! Schema::hasColumn('qr_orders', 'payment_method')) {
                // payment.method (transfer|online|live) — для фильтра/отчётности.
                $table->string('payment_method', 32)->nullable()->index()->after('total_price');
            }
            if (! Schema::hasColumn('qr_orders', 'promo_code')) {
                // Первый промокод (payment.promo_codes[0]) — справочно, для отчётности.
                $table->string('promo_code', 64)->nullable()->index()->after('payment_method');
            }
            if (! Schema::hasColumn('qr_orders', 'paid_at')) {
                // order_data.paid_at — момент оплаты (отдельно от issued_at = момент выдачи билетов).
                $table->timestamp('paid_at')->nullable()->after('issued_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('qr_orders', static function (Blueprint $table): void {
            foreach (['external_order_no', 'payment_method', 'promo_code', 'paid_at'] as $column) {
                if (Schema::hasColumn('qr_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
