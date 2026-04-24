<?php

declare(strict_types=1);

use App\Http\Controllers\Location\LocationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/location')->middleware(['auth:api', 'role:admin'])->group(static function (): void {
    Route::post('/getList', [LocationController::class, 'getList']);
    Route::get('/getItem/{id}', [LocationController::class, 'getItem']);
    Route::post('/create', [LocationController::class, 'create']);
    Route::post('/edit/{id}', [LocationController::class, 'edit']);
    Route::delete('/delete/{id}', [LocationController::class, 'delete']);
});

Route::prefix('v1/location')->middleware(['auth:api', 'role:admin,curator,curator_pusher'])->group(static function (): void {
    Route::post('/getListForCurator', [LocationController::class, 'getList']);
});
