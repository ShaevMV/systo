<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateFriendlyTicket extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('friendly_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('fio');
            $table->string('seller');
            $table->float('price');
            $table->timestamps();
        });
        DB::statement('alter table friendly_tickets AUTO_INCREMENT = 30000');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('friendly_tickets');
    }
}
