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
        Schema::create('tickets', static function (Blueprint $table) {
            $table->uuid('id');

            $table->uuid('order_ticket_id');
            $table->foreign('order_ticket_id')->references('id')->on('order_tickets');

            $table->integer('number');
            $table->string('name');
            $table->string('status')->default('new');

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
        Schema::dropIfExists('tickets');
    }
};
