<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Привязки шаблонов к действиям (Часть B): какой шаблон письма и PDF-билета использовать для
 * пары (тип заказа + тип билета) на фестивале, плюс дефолт-fallback.
 *
 * NULL в festival_id/order_type/ticket_type_id = wildcard («любой»). Резолвер выбирает самую
 * специфичную активную привязку; нет совпадения → is_default; нет дефолта → старое поведение
 * (slug из ticket_type_festival) — полная обратная совместимость.
 *
 * Спека: .claude/specs/template-aggregate-and-bindings.md (Часть B).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_bindings', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('festival_id')->nullable()->index();       // null = любой фестиваль
            $table->string('order_type', 20)->nullable()->index();  // regular/friendly/list/live; null = любой
            $table->uuid('ticket_type_id')->nullable()->index();    // null = любой тип билета
            $table->uuid('email_template_id')->nullable();          // → templates (kind=email)
            $table->uuid('pdf_template_id')->nullable();            // → templates (kind=pdf)
            $table->boolean('is_default')->default(false);          // дефолт-fallback (когда нет совпадения)
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['active', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_bindings');
    }
};
