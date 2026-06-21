<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Эволюция таблицы `comment` под полноценный тред комментариев к заказу (C1).
 *
 * Backward-compatible, без doctrine/dbal `->change()` (его в Backend нет стабильно, TD-25):
 *  - `user_id` → NULLABLE (автор-неorg: baza/qr/system). FK дропаем, MODIFY руками, FK возвращаем nullable.
 *  - `+ author_name` — отображаемое имя автора (org-юзер / ФИО персонала Baza / «qr»).
 *  - `+ author_source` — `org_user` / `baza` / `qr` / `system`. Существующие строки → `org_user` (DEFAULT).
 *
 * Все шаги идемпотентны (Schema::hasColumn-гарды + try на FK), чтобы повторный прогон не падал.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('comment')) {
            return;
        }

        // 1) Делаем user_id nullable. Под FK + без doctrine/dbal: дропаем FK → MODIFY → возвращаем nullable FK.
        // dropForeign требует существующего FK; оборачиваем в try на случай повторного прогона.
        Schema::table('comment', static function (Blueprint $table): void {
            try {
                $table->dropForeign(['user_id']);
            } catch (\Throwable $e) {
                // FK уже снят (повторный прогон) — игнорируем.
            }
        });

        DB::statement('ALTER TABLE comment MODIFY user_id CHAR(36) NULL');

        Schema::table('comment', static function (Blueprint $table): void {
            try {
                // nullable FK допускает NULL — автор может быть не-org (baza/qr/system).
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            } catch (\Throwable $e) {
                // FK уже добавлен (повторный прогон) — игнорируем.
            }
        });

        // 2) Новые колонки треда (с гардом — идемпотентно).
        Schema::table('comment', static function (Blueprint $table): void {
            if (! Schema::hasColumn('comment', 'author_name')) {
                $table->string('author_name')->nullable()->after('comment');
            }
            if (! Schema::hasColumn('comment', 'author_source')) {
                // Бэкафилл существующих строк обеспечивает DEFAULT 'org_user'.
                $table->string('author_source', 20)->default('org_user')->after('author_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('comment')) {
            return;
        }

        Schema::table('comment', static function (Blueprint $table): void {
            if (Schema::hasColumn('comment', 'author_source')) {
                $table->dropColumn('author_source');
            }
            if (Schema::hasColumn('comment', 'author_name')) {
                $table->dropColumn('author_name');
            }
        });

        // Возврат user_id к NOT NULL: дропаем nullable FK → MODIFY NOT NULL → возвращаем строгий FK.
        Schema::table('comment', static function (Blueprint $table): void {
            try {
                $table->dropForeign(['user_id']);
            } catch (\Throwable $e) {
            }
        });

        DB::statement('ALTER TABLE comment MODIFY user_id CHAR(36) NOT NULL');

        Schema::table('comment', static function (Blueprint $table): void {
            try {
                $table->foreign('user_id')->references('id')->on('users');
            } catch (\Throwable $e) {
            }
        });
    }
};
