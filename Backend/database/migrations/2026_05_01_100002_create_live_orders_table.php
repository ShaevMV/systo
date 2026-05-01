<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Shared\Domain\ValueObject\Status;

/**
 * Таблица живых заказов.
 *
 * Живой заказ — покупка карточки live-билета.
 * Жизненный цикл: NEW_FOR_LIVE → PAID_FOR_LIVE → LIVE_TICKET_ISSUED.
 * Номера live-билетов хранятся в таблице tickets (поле number).
 *
 * Kilter: префикс L- (L-1, L-2, ...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_orders', static function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('festival_id');
            $table->foreign('festival_id')->references('id')->on('festivals');

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->uuid('ticket_type_id');
            $table->foreign('ticket_type_id')->references('id')->on('ticket_type');

            $table->string('types_of_payment_id');
            $table->foreign('types_of_payment_id')->references('id')->on('types_of_payment');

            $table->json('ticket');

            $table->string('status')->default(Status::NEW_FOR_LIVE);

            $table->float('price')->default(0);
            $table->float('discount')->default(0);
            $table->string('promo_code')->nullable()->default(null);
            $table->string('phone');
            $table->string('id_buy')->nullable();

            $table->timestamps();
        });

        DB::statement('ALTER TABLE live_orders ADD kilter INT(11) UNIQUE NOT NULL AUTO_INCREMENT FIRST');
        DB::statement('ALTER TABLE live_orders AUTO_INCREMENT = 1');
    }

    public function down(): void
    {
        Schema::dropIfExists('live_orders');
    }
};
