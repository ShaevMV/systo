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
        Schema::create('auto', function (Blueprint $table) {
            $table->id();
            $table->string('curator');
            $table->string('project');
            $table->string('auto');
            $table->text('comment')->nullable();
            $table->uuid('festival_id')->default('9d679bcf-b438-4ddb-ac04-023fa9bff4b3');
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
        Schema::dropIfExists('auto');
    }
};
