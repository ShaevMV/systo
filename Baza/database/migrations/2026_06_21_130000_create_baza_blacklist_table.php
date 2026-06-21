<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Чёрный список отозванных билетов (Ф5, PR-6, реш. B6).
 *
 * Приоритетный канал «не пускать»: когда заказ отменён/возвращён, билет должен быть
 * заблокирован на КПП ДАЖЕ офлайн (иначе вернувший деньги гость пройдёт — прямой ущерб).
 * БЕЗ ПДн (только uuid/kilter/festival), чтобы безопасно возить на телефон. Синкается
 * телефоном приоритетнее снимка. Дельта по updated_at (как снимок).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('baza_blacklist')) {
            return;
        }

        Schema::create('baza_blacklist', function (Blueprint $table) {
            $table->id();                                  // автоинкремент — для синка
            $table->char('ticket_uuid', 36)->nullable()->index(); // id билета (если есть)
            $table->integer('kilter')->nullable()->index();        // номер билета (если нет uuid)
            $table->char('festival_id', 36)->nullable()->index();
            $table->string('reason', 120)->nullable();     // напр. «возврат», «отмена» (без ПДн)
            $table->timestamps();
            $table->index(['festival_id', 'updated_at']);  // под дельту синка
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baza_blacklist');
    }
};
