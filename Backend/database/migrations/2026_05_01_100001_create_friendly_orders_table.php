<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Shared\Domain\ValueObject\Status;

/**
 * Таблица дружеских заказов.
 *
 * Дружеский заказ создаётся пушером от имени гостя.
 * Нет формы оплаты — цена вводится пушером вручную.
 * Гость не имеет личного кабинета.
 *
 * user_id = pusher (владелец заказа — пушер).
 * Kilter: префикс F- (F-1, F-2, ...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friendly_orders', static function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('festival_id');
            $table->foreign('festival_id')->references('id')->on('festivals');

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->uuid('ticket_type_id');
            $table->foreign('ticket_type_id')->references('id')->on('ticket_type');

            $table->json('ticket');

            $table->string('status')->default(Status::PAID);

            $table->float('price')->default(0);

            $table->timestamps();
        });

        DB::statement('ALTER TABLE friendly_orders ADD kilter INT(11) UNIQUE NOT NULL AUTO_INCREMENT FIRST');
        DB::statement('ALTER TABLE friendly_orders AUTO_INCREMENT = 1');
    }

    public function down(): void
    {
        Schema::dropIfExists('friendly_orders');
    }
};
