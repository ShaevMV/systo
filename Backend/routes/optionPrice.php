<?php

declare(strict_types=1);

use App\Http\Controllers\OptionPrice\OptionPriceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/optionPrice')->group(static function (): void {
    Route::post('/getList', [OptionPriceController::class, 'getList']);
    Route::get('/getItem/{id}', [OptionPriceController::class, 'getItem']);

    Route::post('/create', [OptionPriceController::class, 'create'])
        ->middleware('auth:api')
        ->middleware('admin');

    Route::post('/edit/{id}', [OptionPriceController::class, 'edit'])
        ->middleware('auth:api')
        ->middleware('admin');

    Route::delete('/delete/{id}', [OptionPriceController::class, 'delete'])
        ->middleware('auth:api')
        ->middleware('admin');
});
