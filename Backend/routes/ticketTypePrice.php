<?php

declare(strict_types=1);

use App\Http\Controllers\TicketTypePrice\TicketTypePriceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/ticketTypePrice')->group(static function (): void {
    // Чтение списка волн остаётся публичным — цены отображаются на форме покупки
    Route::post('/getList', [TicketTypePriceController::class, 'getList']);
    Route::get('/getItem/{id}', [TicketTypePriceController::class, 'getItem']);

    // Изменения волн — только админ (защита от дурака на уровне доступа)
    Route::post('/create', [TicketTypePriceController::class, 'create'])
        ->middleware('auth:api')
        ->middleware('admin');

    Route::post('/edit/{id}', [TicketTypePriceController::class, 'edit'])
        ->middleware('auth:api')
        ->middleware('admin');

    Route::delete('/delete/{id}', [TicketTypePriceController::class, 'delete'])
        ->middleware('auth:api')
        ->middleware('admin');
});
