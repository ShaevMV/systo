<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot many-to-many между `options` и `ticket_type`.
 *
 * Одна опция может быть привязана к нескольким типам билетов
 * (например, «Саженец» доступен и для «Оргвзноса» и для «Оргвзноса
 * соорганизатора»), и один тип билета может иметь несколько опций.
 *
 * Поле `description` лежит на pivot потому, что описание опции
 * зависит от типа билета: для «Оргвзноса» это может быть один текст,
 * а для «Оргвзноса соорганизатора» — другой. Описание на самой опции
 * было бы общим для всех типов билетов, что бизнесу не подходит.
 *
 * См. `.claude/specs/ticket-options.md` §3 (Доменная модель).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('option_ticket_type', static function (Blueprint $table) {
            $table->uuid('option_id');
            $table->foreign('option_id')->references('id')->on('options')->cascadeOnDelete();

            $table->uuid('ticket_type_id');
            $table->foreign('ticket_type_id')->references('id')->on('ticket_type')->cascadeOnDelete();

            $table->text('description')->nullable()->default(null);

            $table->primary(['option_id', 'ticket_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_ticket_type');
    }
};
