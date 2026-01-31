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
        Schema::table('ticket_type_festival', function (Blueprint $table) {
            $table->string('pdf')->default('');
            $table->string('email')->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_type_festival', function (Blueprint $table) {
            $table->dropColumn('pdf');
            $table->dropColumn('email');
        });
    }
};
