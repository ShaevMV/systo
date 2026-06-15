<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблицы системы шаблонов (AF-3): редактируемые из админки шаблоны писем и PDF-билетов.
 *
 * `slug` намеренно равен текущему имени blade-файла ('pdf','orderToPaid','TypeTicketPdf1',...)
 * → нулевая миграция привязки: существующие колонки-селекторы (ticket_type_festival.pdf/.email,
 * locations.*_template, types_of_payment.email) уже указывают на нужную запись.
 *
 * Движок рендера — Mustache (logic-less, RCE-безопасен by design). body/compiled_html — обычные
 * строковые колонки (без JSON-кастов). Пока активной записи нет — рендер падает на blade-файл.
 *
 * Спека: .claude/specs/template-system.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', static function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->string('slug', 120);                 // = имени blade-файла
            $table->enum('kind', ['email', 'pdf']);
            $table->enum('engine', ['html', 'mjml'])->default('html'); // mjml — только email

            $table->string('title');
            $table->mediumText('body');                  // опубликованный исходник
            $table->mediumText('draft_body')->nullable(); // черновик (не идёт в прод)
            $table->mediumText('compiled_html')->nullable(); // кэш скомпилированного MJML (для html = body)

            $table->boolean('active')->default(true);
            $table->boolean('is_system')->default(false); // импортирован из blade (системный)

            $table->timestamps();

            // Один slug — одна запись на тип (email/pdf): резолвер ищет по (slug, kind).
            $table->unique(['slug', 'kind']);
            $table->index(['kind', 'active']);
        });

        Schema::create('template_versions', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('template_id')->index();
            $table->mediumText('body');
            $table->string('comment')->nullable();
            $table->uuid('author_id')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_versions');
        Schema::dropIfExists('templates');
    }
};
