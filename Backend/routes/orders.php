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

        Route::middleware('auth:api')->group(static function (): void {
            Route::get('/item/{id}',   [GuestOrderController::class, 'getItem']);
            Route::get('/user-list',   [GuestOrderController::class, 'getUserList']);

            Route::middleware('role:seller,admin')->group(static function (): void {
                Route::get('/list',               [GuestOrderController::class, 'getList']);
                Route::post('/changeStatus/{id}', [GuestOrderController::class, 'changeStatus']);
            });
        });
    });

    // ----------------------------------------------------------------
    // Дружеские заказы — создаются пушером
    // ----------------------------------------------------------------
    Route::prefix('friendly')->group(static function (): void {
        Route::middleware('auth:api')->group(static function (): void {
            Route::get('/item/{id}',  [FriendlyOrderController::class, 'getItem']);
            Route::get('/user-list',  [FriendlyOrderController::class, 'getUserList']);

            Route::middleware('role:pusher')->group(static function (): void {
                Route::post('/create', [FriendlyOrderController::class, 'create']);
            });

            Route::middleware('role:pusher,admin')->group(static function (): void {
                Route::get('/list',               [FriendlyOrderController::class, 'getList']);
                Route::post('/changeStatus/{id}', [FriendlyOrderController::class, 'changeStatus']);
            });
        });
    });

    // ----------------------------------------------------------------
    // Живые заказы — карточки live-билетов
    // ----------------------------------------------------------------
    Route::prefix('live')->group(static function (): void {
        Route::middleware('auth:api')->group(static function (): void {
            Route::get('/item/{id}',  [LiveOrderController::class, 'getItem']);
            Route::get('/user-list',  [LiveOrderController::class, 'getUserList']);

            Route::middleware('role:seller,admin')->group(static function (): void {
                Route::get('/list',               [LiveOrderController::class, 'getList']);
                Route::post('/create',            [LiveOrderController::class, 'create']);
                Route::post('/changeStatus/{id}', [LiveOrderController::class, 'changeStatus']);
            });
        });
    });
});
