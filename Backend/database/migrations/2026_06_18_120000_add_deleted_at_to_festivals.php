<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Мягкое удаление фестивалей: фестиваль связан с заказами/билетами/типами
     * билетов/промокодами/локациями — физическое удаление осиротило бы их.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('festivals', 'deleted_at')) {
            Schema::table('festivals', static function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('festivals', 'deleted_at')) {
            Schema::table('festivals', static function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
