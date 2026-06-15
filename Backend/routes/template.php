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

    // Предпросмотр на тестовых данных. DomPDF тяжёлый → отдельный тротлинг.
    Route::post('/preview', [TemplateController::class, 'preview'])->middleware('throttle:20,1');
});
