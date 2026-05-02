<?php

declare(strict_types=1);

use App\Http\Controllers\Orders\FriendlyOrderController;
use App\Http\Controllers\Orders\GuestOrderController;
use App\Http\Controllers\Orders\LiveOrderController;
use Illuminate\Support\Facades\Route;

/**
 * Маршруты новой системы заказов (v2).
 *
 * Все маршруты под префиксом /api/v2/orders/.
 * Работают параллельно со старыми /api/v1/order/* — миграция постепенная.
 */
Route::prefix('v2/orders')->group(static function (): void {

    // ----------------------------------------------------------------
    // Гостевые заказы — покупка через сайт
    // ----------------------------------------------------------------
    Route::prefix('guest')->group(static function (): void {
        Route::post('/create', [GuestOrderController::class, 'create']);

        Route::post('/changeStatus/{id}', [GuestOrderController::class, 'changeStatus'])
            ->middleware('auth:api')
            ->middleware('role:seller,admin');
    });

    // ----------------------------------------------------------------
    // Дружеские заказы — создаются пушером
    // ----------------------------------------------------------------
    Route::prefix('friendly')->group(static function (): void {
        Route::post('/create', [FriendlyOrderController::class, 'create'])
            ->middleware('auth:api')
            ->middleware('role:pusher');

        Route::post('/changeStatus/{id}', [FriendlyOrderController::class, 'changeStatus'])
            ->middleware('auth:api')
            ->middleware('role:pusher,admin');
    });

    // ----------------------------------------------------------------
    // Живые заказы — карточки live-билетов
    // ----------------------------------------------------------------
    Route::prefix('live')->group(static function (): void {
        Route::post('/create', [LiveOrderController::class, 'create'])
            ->middleware('auth:api')
            ->middleware('role:seller,admin');

        Route::post('/changeStatus/{id}', [LiveOrderController::class, 'changeStatus'])
            ->middleware('auth:api')
            ->middleware('role:seller,admin');
    });
});
