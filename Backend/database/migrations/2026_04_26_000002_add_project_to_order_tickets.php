<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_tickets', function (Blueprint $table): void {
            $table->string('project')->nullable()->after('location_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_tickets', function (Blueprint $table): void {
            $table->dropColumn('project');
        });
    }
};
