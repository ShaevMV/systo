<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('el_tickets', function (Blueprint $table) {
            $table->uuid('type_ticket_id')->default(null);
            $table->string('type_ticket')->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('el_tickets', function (Blueprint $table) {
            $table->dropColumn('type_ticket_id');
            $table->dropColumn('type_ticket');
        });
    }
};
