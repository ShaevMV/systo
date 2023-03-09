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
        Schema::create('tickets_friendly', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('order_ticket_id');
            $table->foreign('order_ticket_id')->references('id')->on('order_friendly');
            $table->string('name');
            $table->string('status')->default('new');
            $table->softDeletes();
            $table->timestamps();
        });

        DB::statement('alter table tickets_friendly
    add kilter int(11) UNIQUE NOT NULL AUTO_INCREMENT FIRST');
        DB::statement('alter table tickets_friendly AUTO_INCREMENT = 30000');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets_friendly');
    }
};
