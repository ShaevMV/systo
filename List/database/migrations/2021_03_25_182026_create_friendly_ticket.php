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
        Schema::create('list_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('fio');
            $table->string('project');
            $table->string('curator');
            $table->timestamps();
        });
        DB::statement('alter table list_tickets AUTO_INCREMENT = 50000');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('list_tickets');
    }
}
