<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Shared\Domain\ValueObject\Status;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('order_tickets', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('guests')->nullable(false);

            $table->uuid('festival_id');
            $table->foreign('festival_id')->references('id')->on('festivals');

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->uuid('ticket_type_id');
            $table->foreign('ticket_type_id')->references('id')->on('ticket_type');

            $table->string('promo_code')->nullable()->default(null);
            $table->string('id_buy')->nullable(false);
            $table->string('phone')->nullable(false);

            $table->string('types_of_payment_id');
            $table->foreign('types_of_payment_id')->references('id')->on('types_of_payment');

            $table->float('price')->default(0);
            $table->float('discount')->default(0);

            $table->string('status')->nullable(false)->default(Status::NEW);
            $table->string('date')->nullable(false);

            $table->timestamps();
        });
        DB::statement('alter table order_tickets add kilter int(11) UNIQUE NOT NULL AUTO_INCREMENT FIRST');
        DB::statement('alter table order_tickets AUTO_INCREMENT = 1000');
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
