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
        Schema::create('friendly_tickets', function (Blueprint $table) {
            $table->id();
            $table->integer('kilter');
            $table->string('project');
            $table->string('seller');
            $table->string('email')->nullable(false);
            $table->string('name')->nullable(false);
            $table->dateTime('date_order')->nullable(false);
            $table->integer('change_id')->nullable();
            $table->dateTime('date_change')->nullable();
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
        Schema::dropIfExists('friendly_tickets');
    }
};
