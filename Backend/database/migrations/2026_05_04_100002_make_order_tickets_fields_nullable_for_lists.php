<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Делаем nullable поля в order_tickets, которые не заполняются для заказов-списков.
 * FK на ticket_type_id и types_of_payment_id в проде отсутствуют (Laravel определил их
 * в исходной миграции, но в реальной БД они не созданы), поэтому работаем только с типами колонок.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_tickets', function (Blueprint $table) {
            $table->uuid('ticket_type_id')->nullable()->default(null)->change();
            $table->string('types_of_payment_id')->nullable()->default(null)->change();
            $table->float('price')->nullable()->default(null)->change();
            $table->float('discount')->nullable()->default(null)->change();
            $table->string('phone')->nullable()->default(null)->change();
            $table->string('id_buy')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_tickets', function (Blueprint $table) {
            $table->uuid('ticket_type_id')->nullable(false)->change();
            $table->string('types_of_payment_id')->nullable(false)->change();
            $table->float('price')->default(0)->change();
            $table->float('discount')->default(0)->change();
            $table->string('phone')->nullable(false)->change();
            $table->string('id_buy')->nullable(false)->change();
        });
    }
};
