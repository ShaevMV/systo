<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('locations', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable()->default(null);

            $table->uuid('questionnaire_type_id')->nullable()->default(null);
            $table->foreign('questionnaire_type_id')->references('id')->on('questionnaire_type');

            $table->uuid('festival_id');
            $table->foreign('festival_id')->references('id')->on('festivals');

            $table->string('email_template')->nullable()->default(null);
            $table->string('pdf_template')->nullable()->default(null);

            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
