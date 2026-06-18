<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ось «тип оплаты» в привязках шаблонов (AF-9): какой шаблон письма/PDF использовать для
 * конкретного типа оплаты. Тип оплаты связан с внешним продавцом (`types_of_payment.user_external_id`),
 * поэтому это даёт «под каждого продавца определённый тип письма» (AF-10).
 *
 * NULL = «любой тип оплаты» (wildcard) → существующие привязки и резолв не меняются (полная
 * обратная совместимость). Сильнейший дискриминатор специфичности (вес 16 — override продавца).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('template_bindings', 'types_of_payment_id')) {
            return;
        }

        Schema::table('template_bindings', static function (Blueprint $table): void {
            $table->uuid('types_of_payment_id')->nullable()->index()->after('ticket_type_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('template_bindings', 'types_of_payment_id')) {
            return;
        }

        Schema::table('template_bindings', static function (Blueprint $table): void {
            $table->dropColumn('types_of_payment_id');
        });
    }
};
