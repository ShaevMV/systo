<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_tickets', function (Blueprint $table) {
            $table->uuid('location_id')->nullable()->default(null)->after('ticket_type_id');
            $table->foreign('location_id')->references('id')->on('locations');

            $table->uuid('curator_id')->nullable()->default(null)->after('friendly_id');
            $table->foreign('curator_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::table('order_tickets', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');

            $table->dropForeign(['curator_id']);
            $table->dropColumn('curator_id');
        });
    }
};
