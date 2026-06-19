<?php

declare(strict_types=1);

use App\Http\Controllers\BazaDelivery\BazaDeliveryController;
use Illuminate\Support\Facades\Route;

// Контроль доставки билетов в Baza (AF-4): список/деталь/повтор/статистика. admin-only (содержит ПДн).
// Запись в Baza выполняет BazaDeliveryDispatcher/DeliverTicketToBazaJob; здесь — только просмотр и повтор.
Route::prefix('v1/bazaDelivery')->middleware(['auth:api', 'admin'])->group(static function (): void {
    Route::post('/getList', [BazaDeliveryController::class, 'getList']);
    Route::get('/getItem/{id}', [BazaDeliveryController::class, 'getItem']);
    Route::post('/resend/{id}', [BazaDeliveryController::class, 'resend']);
    Route::post('/getStats', [BazaDeliveryController::class, 'getStats']);
});
