<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица для автомобилей, привязанных к заказам-спискам.
 * number — текстовый номер авто, дубли допустимы.
 * Soft delete для синхронизации статусов с базой Baza.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('auto', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_ticket_id');
            $table->string('number');
            $table->softDeletes();
            $table->timestamps();

            $table->index('order_ticket_id');
            $table->foreign('order_ticket_id')
                ->references('id')->on('order_tickets')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto');
    }
};
