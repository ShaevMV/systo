<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_tickets', static function (Blueprint $table) {
            $table->uuid('curator_id')->nullable()->after('friendly_id');
            $table->uuid('location_id')->nullable()->after('curator_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_tickets', static function (Blueprint $table) {
            $table->dropColumn(['curator_id', 'location_id']);
        });
    }
};
