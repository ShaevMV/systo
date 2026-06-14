<?php

declare(strict_types=1);

use App\Http\Controllers\QrOrder\QrOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/qrOrder')->group(static function (): void {
    // API №1 — приём заказа от витрины qr (server-to-server).
    // TODO(безопасность): добавить аутентификацию канала (service-token/подпись).
    Route::post('/create', [QrOrderController::class, 'create']);

    // Чтение принятого заказа — только админ (содержит ПДн).
    Route::get('/getItem/{id}', [QrOrderController::class, 'getItem'])
        ->middleware('auth:api')
        ->middleware('admin');
});
