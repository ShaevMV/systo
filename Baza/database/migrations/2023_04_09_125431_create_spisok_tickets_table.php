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
        Schema::create('spisok_tickets', function (Blueprint $table) {
            $table->id();
            $table->integer('kilter')->nullable(false);
            $table->string('project')->nullable(false);
            $table->string('curator')->nullable(false);
            $table->string('email')->nullable(false);
            $table->string('name')->nullable(false);
            $table->dateTime('date_order')->nullable(false);
            $table->text('comment')->nullable(false);
            $table->integer('change_id')->nullable();
            $table->dateTime('date_change')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('spisok_tickets');
    }
};
