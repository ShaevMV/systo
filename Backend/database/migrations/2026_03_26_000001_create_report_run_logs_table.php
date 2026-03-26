<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_run_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_config_id')->constrained('report_configs')->onDelete('cascade');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->integer('exported_rows')->default(0);
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('report_config_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_run_logs');
    }
};
