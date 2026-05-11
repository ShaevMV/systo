<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_type', function (Blueprint $table) {
            $table->boolean('is_parking')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('ticket_type', function (Blueprint $table) {
            $table->dropColumn('is_parking');
        });
    }
};
