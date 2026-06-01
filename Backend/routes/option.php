<?php

declare(strict_types=1);

use App\Http\Controllers\Option\OptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/option')->group(static function (): void {
    // Read публично — нужны фронту формы покупки
    Route::post('/getList', [OptionController::class, 'getList']);
    Route::get('/getItem/{id}', [OptionController::class, 'getItem']);

    // Read-модель для формы покупки билета: активные опции конкретного типа
    Route::get(
        '/getActiveForTicketType/{ticketTypeId}',
        [OptionController::class, 'getActiveForTicketType']
    );

    // Write только админ
    Route::post('/create', [OptionController::class, 'create'])
        ->middleware('auth:api')
        ->middleware('admin');

    Route::post('/edit/{id}', [OptionController::class, 'edit'])
        ->middleware('auth:api')
        ->middleware('admin');

    Route::delete('/delete/{id}', [OptionController::class, 'delete'])
        ->middleware('auth:api')
        ->middleware('admin');
});
