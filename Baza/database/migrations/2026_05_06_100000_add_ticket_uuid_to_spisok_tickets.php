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
            $table->char('ticket_uuid', 36)->nullable()->default(null)->after('id');
            $table->index('ticket_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('spisok_tickets', function (Blueprint $table) {
            $table->dropIndex(['ticket_uuid']);
            $table->dropColumn('ticket_uuid');
        });
    }
};
