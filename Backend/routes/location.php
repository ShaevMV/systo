<?php

declare(strict_types=1);

use App\Http\Controllers\Location\LocationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/location')->group(static function (): void {
    Route::post('/getList', [LocationController::class, 'getList']);
    Route::get('/getItem/{id}', [LocationController::class, 'getItem']);

    Route::post('/create', [LocationController::class, 'create'])
        ->middleware('auth:api')
        ->middleware('admin');

    Route::post('/edit/{id}', [LocationController::class, 'edit'])
        ->middleware('auth:api')
        ->middleware('admin');

    Route::delete('/delete/{id}', [LocationController::class, 'delete'])
        ->middleware('auth:api')
        ->middleware('admin');
});
