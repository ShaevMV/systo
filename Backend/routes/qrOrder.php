<?php

declare(strict_types=1);

use App\Http\Controllers\QrOrder\QrOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/qrOrder')->group(static function (): void {
    // API №1 — приём заказа от витрины qr (server-to-server).
    // TODO(безопасность): добавить аутентификацию канала (service-token/подпись).
    Route::post('/create', [QrOrderController::class, 'create']);

    // API №2 — смена статуса заказа (server-to-server от qr; шаг 2b запустит выдачу билетов).
    // TODO(безопасность): аутентификация канала.
    Route::post('/changeStatus/{id}', [QrOrderController::class, 'changeStatus']);

    // Чтение принятого заказа — только админ (содержит ПДн).
    Route::get('/getItem/{id}', [QrOrderController::class, 'getItem'])
        ->middleware('auth:api')
        ->middleware('admin');
});
