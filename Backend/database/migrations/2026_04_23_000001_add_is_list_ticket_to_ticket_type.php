<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_type', static function (Blueprint $table) {
            $table->boolean('is_list_ticket')->default(false)->after('is_live_ticket');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_type', static function (Blueprint $table) {
            $table->dropColumn('is_list_ticket');
        });
    }
};
