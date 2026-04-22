<?php

declare(strict_types=1);

use App\Http\Controllers\PromoCode\PromoCodeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/promoCode')->group(static function (): void {

    Route::get('/getListPromoCode',
        [PromoCodeController::class, 'getListPromoCode']);

    Route::get('/getItemPromoCode/{idPromoCode?}',
        [PromoCodeController::class, 'getItemPromoCode']);

    Route::post('/savePromoCode/{idPromoCode?}',
        [PromoCodeController::class, 'savePromoCode']);

    Route::post('/find/{promoCode?}',
        [PromoCodeController::class, 'findPromoCode']);

    Route::post('/savePromoCodeForBot/{idPromoCode?}',
        [PromoCodeController::class, 'savePromoCodeForBot'])
        ->middleware('bot');
})->middleware('auth:api')
    ->middleware('admin');

