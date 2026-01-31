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
        Schema::table('friendly_tickets', function (Blueprint $table) {
            $table->boolean('is_need_seedling')->default(false);
        });
        Schema::table('el_tickets', function (Blueprint $table) {
            $table->boolean('is_need_seedling')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('friendly_tickets', function (Blueprint $table) {
            $table->dropColumn('is_need_seedling');
        });
        Schema::table('el_tickets', function (Blueprint $table) {
            $table->dropColumn('is_need_seedling');
        });
    }
};
