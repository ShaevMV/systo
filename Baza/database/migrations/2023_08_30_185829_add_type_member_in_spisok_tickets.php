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
            $table->string('type_member')->default('artist')->comment('тип участника');
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
            $table->dropColumn('type_member');
        });
    }
};
