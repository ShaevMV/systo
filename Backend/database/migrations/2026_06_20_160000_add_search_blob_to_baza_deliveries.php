<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Богатые данные гостя для поискового индекса Baza (ticket_search, поиск без QR).
 *
 * Рядом с subject_blob (узкий TicketResponse для записи в таблицу впуска) храним search_blob —
 * base64(json) богатых полей гостя (fio/phone/telegram/car/child/...). DeliverTicketToBazaJob
 * прикладывает их к ingest-запросу как блок `search`, Baza наполняет ticket_search.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('baza_deliveries', 'search_blob')) {
            return;
        }

        Schema::table('baza_deliveries', function (Blueprint $table) {
            $table->longText('search_blob')->nullable()->after('subject_blob');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('baza_deliveries', 'search_blob')) {
            return;
        }

        Schema::table('baza_deliveries', function (Blueprint $table) {
            $table->dropColumn('search_blob');
        });
    }
};
