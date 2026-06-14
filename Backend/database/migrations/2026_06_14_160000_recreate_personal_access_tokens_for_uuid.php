<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Пересоздаёт personal_access_tokens под UUID-владельцев.
 *
 * Проектная таблица — старой схемы Sanctum: tokenable_id = BIGINT (morphs) и нет expires_at.
 * Но id пользователя в проекте — UUID, поэтому createToken() падал
 * («Incorrect integer value … for column tokenable_id»). Sanctum в проекте раньше не
 * использовался → таблица пустая везде, безопасно дропнуть и создать заново с uuidMorphs
 * и колонкой expires_at (актуальная схема Sanctum 3.x).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('personal_access_tokens');

        Schema::create('personal_access_tokens', static function (Blueprint $table): void {
            $table->id();
            $table->uuidMorphs('tokenable'); // tokenable_id = char(36) под UUID пользователя
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Откат к старой схеме Sanctum (BIGINT-владелец, без expires_at).
        Schema::dropIfExists('personal_access_tokens');

        Schema::create('personal_access_tokens', static function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }
};
