<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Индекс под дельту офлайн-снимка (Ф5, PR-3): GET /api/snapshot фильтрует
 * по (festival_id, updated_at) при since-дельте. На слабом ноутбуке-кэше КПП
 * частые дельты без индекса = full-scan. Идемпотентно (проверка наличия индекса).
 */
return new class extends Migration
{
    private const INDEX = 'ticket_search_festival_id_updated_at_index';

    public function up(): void
    {
        if (! Schema::hasTable('ticket_search')) {
            return;
        }

        $exists = collect(DB::select(
            'SHOW INDEX FROM ticket_search WHERE Key_name = ?',
            [self::INDEX],
        ))->isNotEmpty();

        if ($exists) {
            return;
        }

        Schema::table('ticket_search', function (Blueprint $table) {
            $table->index(['festival_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ticket_search')) {
            return;
        }

        Schema::table('ticket_search', function (Blueprint $table) {
            $table->dropIndex(self::INDEX);
        });
    }
};
