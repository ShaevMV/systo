<?php

declare(strict_types=1);

use App\Http\Controllers\Template\TemplateBindingController;
use Illuminate\Support\Facades\Route;

// Привязки шаблонов к (festival, order_type, ticket_type) → email/pdf шаблон + дефолт (AF-3, Часть B).
// admin-only. Резолв применяется при выдаче билетов (InMemoryMySqlTicketsRepository::getTicket).
Route::prefix('v1/templateBinding')->middleware(['auth:api', 'admin'])->group(static function (): void {
    Route::post('/getList', [TemplateBindingController::class, 'getList']);
    Route::get('/getItem/{id}', [TemplateBindingController::class, 'getItem']);
    Route::post('/create', [TemplateBindingController::class, 'create']);
    Route::post('/edit/{id}', [TemplateBindingController::class, 'edit']);
    Route::delete('/delete/{id}', [TemplateBindingController::class, 'delete']);
});
