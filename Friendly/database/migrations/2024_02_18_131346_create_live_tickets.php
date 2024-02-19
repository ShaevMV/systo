<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLiveTickets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('live_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('kilter');
            $table->string('email');
            $table->string('fio');
            $table->string('seller');
            $table->float('price');
            $table->string('fio_friendly');
            $table->foreignId('user_id')->nullable()->index()->default(1);
            $table->text('comment');
            $table->uuid('festival_id');
            $table->string('phone');
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
        Schema::dropIfExists('live_tickets');
    }
}
