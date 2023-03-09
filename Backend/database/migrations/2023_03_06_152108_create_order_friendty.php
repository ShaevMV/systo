<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tickets\Shared\Domain\ValueObject\Status;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('order_friendly', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('guests')->nullable(false);

            $table->uuid('festival_id');
            $table->foreign('festival_id')->references('id')->on('festivals');

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->float('price')->default(0);

            $table->string('status')->nullable(false)->default(Status::NEW);

            $table->timestamps();
        });

        DB::statement('alter table order_friendly add kilter int(11) UNIQUE NOT NULL AUTO_INCREMENT FIRST');
        DB::statement('alter table order_friendly AUTO_INCREMENT = 1000');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('order_friendly');
    }
};
