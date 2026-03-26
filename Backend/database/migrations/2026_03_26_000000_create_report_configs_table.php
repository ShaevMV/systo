<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('report_type')->default('friendly_summary');
            $table->string('spreadsheet_id');
            $table->string('sheet_name')->default('Sheet1');
            $table->integer('start_row')->default(1);
            $table->json('filters')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('cron_expression')->default('0 2 * * *');
            $table->string('timezone')->default('Europe/Moscow');
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_run_status')->nullable();
            $table->text('last_run_message')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'cron_expression']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_configs');
    }
};
