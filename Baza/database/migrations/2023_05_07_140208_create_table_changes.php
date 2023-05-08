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
        Schema::create('changes', function (Blueprint $table) {
            $table->id();
            $table->json('user_id')->nullable(false);
            $table->integer('count_live_tickets')->default(0);
            $table->integer('count_el_tickets')->default(0);
            $table->integer('count_drug_tickets')->default(0);
            $table->integer('count_spisok_tickets')->default(0);
            $table->dateTime('start')->nullable()->default(null);
            $table->dateTime('end')->nullable()->default(null);
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
        Schema::dropIfExists('changes');
    }
};
