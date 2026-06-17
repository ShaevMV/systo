<?php

declare(strict_types=1);

use App\Http\Controllers\Template\TemplateController;
use Illuminate\Support\Facades\Route;

// Шаблоны писем/PDF (AF-3) — admin-only CRUD. Рендер из БД с fallback на blade.
Route::prefix('v1/template')->middleware(['auth:api', 'admin'])->group(static function (): void {
    Route::post('/getList', [TemplateController::class, 'getList']);
    Route::get('/getItem/{id}', [TemplateController::class, 'getItem']);
    Route::post('/create', [TemplateController::class, 'create']);
    Route::post('/edit/{id}', [TemplateController::class, 'edit']);
    Route::post('/activate/{id}', [TemplateController::class, 'activate']);

    // Черновик / публикация (со снапшотом версии) / версии тела / откат.
    Route::post('/saveDraft/{id}', [TemplateController::class, 'saveDraft']);
    Route::post('/publish/{id}', [TemplateController::class, 'publish']);
    Route::get('/versions/{id}', [TemplateController::class, 'versions']);
    Route::post('/rollback/{id}/{versionId}', [TemplateController::class, 'rollback']);

    // Журнал действий (domain_history, aggregate_type=template): кто/что/когда менял.
    Route::get('/history/{id}', [TemplateController::class, 'getHistory']);

    // Палитра плейсхолдеров для редактора (?kind=email|pdf).
    Route::get('/variables/{slug}', [TemplateController::class, 'variables']);

    // Предпросмотр на тестовых данных. DomPDF тяжёлый → отдельный тротлинг.
    Route::post('/preview', [TemplateController::class, 'preview'])->middleware('throttle:20,1');

    // Загрузка картинки (фон PDF-билета / иллюстрации) → public storage, возвращает URL.
    Route::post('/uploadImage', [TemplateController::class, 'uploadImage'])->middleware('throttle:20,1');
});
