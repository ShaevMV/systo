<?php


declare(strict_types=1);

use App\Http\Controllers\Festival\FestivalController;
use App\Http\Controllers\TypesOfPayment\TypesOfPaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/typesOfPayment')->group(static function (): void {
    // Получить полный список типов оплаты
    Route::post('/getList',[TypesOfPaymentController::class, 'getList']);
    Route::get('/getItem/{id}',[TypesOfPaymentController::class, 'getItem']);

});
