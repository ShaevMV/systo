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
        Schema::create('order_tickets', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('guests');


            $table->string('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->string('ticket_type_id');
            $table->foreign('ticket_type_id')->references('id')->on('ticket_type');

            $table->string('promo_code_id');
            $table->foreign('promo_code_id')->references('id')->on('promo_code');

            $table->string('types_of_payment_id');
            $table->foreign('types_of_payment_id')->references('id')->on('types_of_payment');
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
        Schema::dropIfExists('order_tickets');
    }
};
