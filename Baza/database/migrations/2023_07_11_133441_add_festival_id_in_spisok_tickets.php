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
        Schema::table('spisok_tickets', function (Blueprint $table) {
            $table->uuid('festival_id')->default('9d679bcf-b438-4ddb-ac04-023fa9bff4b2');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('spisok_tickets', function (Blueprint $table) {
            $table->dropColumn('festival_id');
        });
    }
};
