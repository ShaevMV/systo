<?php

declare(strict_types=1);

use App\Http\Controllers\QrOrder\QrOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/qrOrder')->group(static function (): void {
    // Приём заказа от витрины qr. Два режима (см. QrOrderApplication::create):
    //  - «создан» → org шлёт письмо «заказ создан», билеты НЕ выпускает (двухшаговый цикл);
    //  - «оплачен» → org сразу выпускает билеты (одношаговый приём).
    // Канал закрыт сервисным ключом qr (заголовок X-QR-Token, middleware qr.ingest) +
    // опционально allowlist IP qr на nginx. Эндпоинт хранит ПДн.
    Route::post('/create', [QrOrderController::class, 'create'])
        ->middleware('qr.ingest');

    // Смена статуса принятого заказа (S2S, тот же X-QR-Token). Переход в «оплачен» запускает
    // выдачу билетов (PDF/письма) — один раз, защита по issued_at (повторный «оплачен» от
    // ретраев qr не выдаёт билеты снова). Нужен для двухшагового цикла «создан» → «оплачен».
    Route::post('/changeStatus/{id}', [QrOrderController::class, 'changeStatus'])
        ->middleware('qr.ingest');

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

    // История заказа (created/status_changed/step_*/issued, actor=qr) — только админ org.
    Route::get('/getHistory/{id}', [QrOrderController::class, 'getHistory'])
        ->middleware('auth:api')
        ->middleware('admin');

    // Ссылки на PDF билетов заказа (скачивание из админки) — только admin.
    Route::get('/getTicketPdf/{id}', [QrOrderController::class, 'getTicketPdf'])
        ->middleware('auth:api')
        ->middleware('admin');

    // Весь путь заказа: приём → билеты(PDF) → письма(статусы) → история шагов — только admin.
    Route::get('/getPipeline/{id}', [QrOrderController::class, 'getPipeline'])
        ->middleware('auth:api')
        ->middleware('admin');
});
