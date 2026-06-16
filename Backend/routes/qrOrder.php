<?php

declare(strict_types=1);

use App\Http\Controllers\QrOrder\QrOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/qrOrder')->group(static function (): void {
    // S2S-канал qr→org: Sanctum-токен сервис-аккаунта со scope (ability) "qr:ingest".
    // Токен выпускается командой `php artisan qr:issue-token` и хранится в .env qr-сервера;
    // qr шлёт его заголовком `Authorization: Bearer <token>`.
    // Приём заказа: qr присылает его уже в статусе «оплачен» → org сразу выпускает билеты
    // (см. QrOrderApplication::create). Эндпоинт выпускает билеты и хранит ПДн → НЕ публичный.
    Route::post('/create', [QrOrderController::class, 'create'])
        ->middleware(['auth:sanctum', 'abilities:qr:ingest']);

    // Список принятых заказов для админки org (read-only): фильтры + пагинация. Содержит ПДн.
    Route::post('/getList', [QrOrderController::class, 'getList'])
        ->middleware('auth:api')
        ->middleware('admin');

    // Сводные метрики для дашборда (read-only): заказы + выручка в разрезах. Только admin.
    Route::post('/getStats', [QrOrderController::class, 'getStats'])
        ->middleware('auth:api')
        ->middleware('admin');

    // Чтение принятого заказа — только админ org (JWT), содержит ПДн.
    Route::get('/getItem/{id}', [QrOrderController::class, 'getItem'])
        ->middleware('auth:api')
        ->middleware('admin');

    // История заказа (created/status_changed/issued, actor=qr) — только админ org.
    Route::get('/getHistory/{id}', [QrOrderController::class, 'getHistory'])
        ->middleware('auth:api')
        ->middleware('admin');
});
