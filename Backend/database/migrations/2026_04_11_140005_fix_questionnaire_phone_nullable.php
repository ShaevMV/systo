<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Исправляет ограничение NOT NULL для колонки phone в таблице questionnaire.
 *
 * Проблема: колонка phone была создана с Null: NO, но после переноса данных
 * в JSON-колонку `data` мы должны иметь возможность вставлять NULL в корневую
 * колонку phone, когда данные хранятся только в data JSON.
 *
 * Это позволяет:
 * - Хранить данные в JSON `data` без дублирования в корневых колонках
 * - DTO::fromState() правильно читать поля из data
 * - API возвращать phone, telegram и другие поля из JSON
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questionnaire', function (Blueprint $table) {
            // Делаем phone NULLable
            if (Schema::hasColumn('questionnaire', 'phone')) {
                $table->string('phone')->nullable()->default(null)->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('questionnaire', function (Blueprint $table) {
            // Возвращаем NOT NULL (с риском потери данных)
            if (Schema::hasColumn('questionnaire', 'phone')) {
                $table->string('phone')->nullable(false)->change();
            }
        });
    }
};
