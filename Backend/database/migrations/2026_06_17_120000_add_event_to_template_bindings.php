<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ось «событие» в привязках шаблонов: какой шаблон письма/PDF использовать для конкретного
 * события (оплата/отмена/изменение/регистрация и т.п.), а не только для пары
 * (festival + order_type + ticket_type).
 *
 * NULL = «любое событие» (wildcard) → существующие привязки и резолв PDF/выдачи НЕ меняются
 * (полная обратная совместимость). Каталог событий — Tickets\EmailDelivery\Domain\EmailEvent.
 * Спека: .claude/specs/email-delivery-system.md (Часть 1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('template_bindings', static function (Blueprint $table): void {
            // null = любое событие (wildcard). Сильнейший дискриминатор специфичности (вес 8).
            $table->string('event', 40)->nullable()->index()->after('order_type');
        });
    }

    public function down(): void
    {
        Schema::table('template_bindings', static function (Blueprint $table): void {
            $table->dropColumn('event');
        });
    }
};
