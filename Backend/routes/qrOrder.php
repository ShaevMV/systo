<?php

declare(strict_types=1);

use App\Http\Controllers\QrOrder\QrOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/qrOrder')->group(static function (): void {
    // S2S-канал qr→org: Sanctum-токен сервис-аккаунта со scope (ability) "qr:ingest".
    // Токен выпускается командой `php artisan qr:issue-token` и хранится в .env qr-сервера;
    // qr шлёт его заголовком `Authorization: Bearer <token>`.
    Route::middleware(['auth:sanctum', 'abilities:qr:ingest'])->group(static function (): void {
        // API №1 — приём заказа от витрины qr.
        Route::post('/create', [QrOrderController::class, 'create']);

        // API №2 — смена статуса заказа (шаг 2b при «оплачен» запускает выдачу билетов).
        Route::post('/changeStatus/{id}', [QrOrderController::class, 'changeStatus']);
    });

    // Чтение принятого заказа — только админ org (JWT), содержит ПДн.
    Route::get('/getItem/{id}', [QrOrderController::class, 'getItem'])
        ->middleware('auth:api')
        ->middleware('admin');

    // История заказа (created/status_changed/issued, actor=qr) — только админ org.
    Route::get('/getHistory/{id}', [QrOrderController::class, 'getHistory'])
        ->middleware('auth:api')
        ->middleware('admin');
});
