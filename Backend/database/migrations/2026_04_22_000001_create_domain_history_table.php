<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_history', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('aggregate_id', 36)->comment('UUID агрегата');
            $table->string('aggregate_type', 50)->comment('order | ticket');
            $table->string('event_name', 100)->comment('status_changed | ticket_data_changed | ...');
            $table->json('payload')->comment('{"from":..., "to":..., "comment":...}');
            $table->string('actor_id', 36)->nullable()->comment('UUID пользователя или NULL');
            $table->string('actor_type', 20)->default('user')->comment('user | system | artisan');
            $table->timestamp('occurred_at')->useCurrent();

            $table->index(['aggregate_id', 'occurred_at'], 'idx_aggregate_history');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_history');
    }
};
