<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Добавляет order_id (UUID заказа-списка из systo) в таблицу auto.
 * Позволяет удалять все авто заказа одной командой и связывать запись с заказом.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('auto', function (Blueprint $table) {
            $table->char('order_id', 36)->nullable()->default(null)->after('id');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('auto', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropColumn('order_id');
        });
    }
};
