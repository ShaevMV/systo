<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Добавляет ticket_uuid (UUID билета из systo) в таблицу spisok_tickets.
 * Нужно для точной идентификации записи при смене данных или отмене:
 * вместо поиска по kilter — ищем по ticket_uuid, затем ставим status=cancel.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('spisok_tickets', function (Blueprint $table) {
            $table->uuid('ticket_uuid')->nullable(true)->comment('ссылка на электронный билет');
        });
    }

    public function down(): void
    {
        Schema::table('spisok_tickets', function (Blueprint $table) {
            $table->dropColumn('ticket_uuid');
        });
    }
};
