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
        Schema::table('changes', function (Blueprint $table) {
            $table->integer('count_parking_tickets')->default(0);
            $table->integer('count_parking_free_tickets')->default(0);
            $table->integer('count_parking_cross-country_tickets')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('changes', function (Blueprint $table) {
            $table->dropColumn('count_parking_tickets');
            $table->dropColumn('count_parking_free_tickets');
            $table->dropColumn('count_parking_cross-country_tickets');
        });
    }
};
