<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Поле "проект" — текстовый идентификатор проекта/группы для заказов-списков.
 * Заполняется куратором при создании заказа-списка, используется для группировки и фильтрации.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_tickets', function (Blueprint $table) {
            $table->string('project')->nullable()->default(null)->after('curator_id');
            $table->index('project');
        });
    }

    public function down(): void
    {
        Schema::table('order_tickets', function (Blueprint $table) {
            $table->dropIndex(['project']);
            $table->dropColumn('project');
        });
    }
};
