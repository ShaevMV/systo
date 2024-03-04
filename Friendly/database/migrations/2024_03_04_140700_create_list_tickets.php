<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateListTickets extends Migration
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
            $table->string('phone');
            $table->foreignId('user_id')->nullable()->index()->default(1);
            $table->text('comment')->default(null);
            $table->uuid('festival_id');
            $table->timestamps();
        });
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
