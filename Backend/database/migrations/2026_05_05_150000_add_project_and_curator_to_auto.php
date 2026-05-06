<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Денормализация таблицы `auto`: дублируем project и curator (composite "email | ФИО")
 * с момента добавления авто. Это упрощает запросы и синхронизацию с Baza.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('auto', function (Blueprint $table) {
            $table->string('project')->nullable()->default(null)->after('number');
            $table->string('curator')->nullable()->default(null)->after('project');

            $table->index('project');
            $table->index('curator');
        });
    }

    public function down(): void
    {
        Schema::table('auto', function (Blueprint $table) {
            $table->dropIndex(['curator']);
            $table->dropIndex(['project']);
            $table->dropColumn(['curator', 'project']);
        });
    }
};
