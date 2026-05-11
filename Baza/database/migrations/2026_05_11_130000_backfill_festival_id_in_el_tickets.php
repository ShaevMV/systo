<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private const FESTIVAL_ID = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';
    private const KILTER_FROM = 7276;

    /**
     * Backfill festival_id для билетов, у которых поле осталось NULL из-за того,
     * что в TicketResponse::toArrayForBaza() оно ранее не попадало в INSERT.
     * Границу выставляем по kilter > 7276 — это диапазон текущего фестиваля.
     */
    public function up(): void
    {
        DB::table('el_tickets')
            ->where('kilter', '>', self::KILTER_FROM)
            ->whereNull('festival_id')
            ->update(['festival_id' => self::FESTIVAL_ID]);
    }

    public function down(): void
    {
        DB::table('el_tickets')
            ->where('kilter', '>', self::KILTER_FROM)
            ->where('festival_id', '=', self::FESTIVAL_ID)
            ->update(['festival_id' => null]);
    }
};
